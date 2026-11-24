<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $other;
    protected User $admin;
    protected LeaveType $annual;
    protected LeaveRequest $leaveRequest;
    protected string $path;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\LeaveTypeSeeder::class);

        $employeeRole = Role::where('name', 'employee')->firstOrFail();
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $this->annual = LeaveType::where('code', 'annual')->firstOrFail();

        $this->owner = $this->makeUser('Owen Owner', 'owner@company.test', $employeeRole->id);
        $this->other = $this->makeUser('Emma Other', 'other@company.test', $employeeRole->id);
        $this->admin = $this->makeUser('Adam Admin', 'admin@company.test', $adminRole->id);

        // A real 1x1 PNG so finfo reports image/png from file content.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==');
        $this->path = 'attachments/photo-preview.png';
        Storage::disk('local')->put($this->path, $png);

        $this->leaveRequest = $this->makeRequest($this->owner, $this->path, 'photo.png');
    }

    private function makeUser(string $name, string $email, int $roleId): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('StrongPass123!@#'),
            'role_id' => $roleId,
            'is_active' => true,
        ]);
    }

    private function makeRequest(User $user, string $path, string $name): LeaveRequest
    {
        return LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $this->annual->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'days' => 1,
            'reason' => 'Attachment preview test',
            'status' => 'pending',
            'attachment_path' => $path,
            'attachment_name' => $name,
            'attachment_original_name' => $name,
        ]);
    }

    private function makePdfRequest(): LeaveRequest
    {
        $path = 'attachments/note-preview.pdf';
        Storage::disk('local')->put(
            $path,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n"
        );

        return $this->makeRequest($this->owner, $path, 'note.pdf');
    }

    public function test_owner_gets_inline_image_preview_with_safe_headers(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('requests.attachment.preview', $this->leaveRequest));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringNotContainsString('attachments/', $response->headers->get('Content-Disposition'));
    }

    public function test_admin_gets_inline_image_preview(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('requests.attachment.preview', $this->leaveRequest));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_another_employee_gets_forbidden(): void
    {
        $this->actingAs($this->other)
            ->get(route('requests.attachment.preview', $this->leaveRequest))
            ->assertForbidden();
    }

    public function test_missing_attachment_returns_not_found(): void
    {
        $missing = $this->makeRequest($this->owner, 'attachments/does-not-exist.png', 'missing.png');

        $this->actingAs($this->owner)
            ->get(route('requests.attachment.preview', $missing))
            ->assertNotFound();
    }

    public function test_pdf_preview_uses_detected_pdf_content_type(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('requests.attachment.preview', $this->makePdfRequest()));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_download_route_still_uses_attachment_disposition(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('requests.attachment', $this->leaveRequest));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringNotContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_original_filename_is_sanitized_and_never_used_as_a_path(): void
    {
        $longName = str_repeat('a', 130) . '.pdf';
        $file = UploadedFile::fake()->create($longName, 10, 'application/pdf');

        $date = now()->addDay()->toDateString();

        $this->actingAs($this->owner)->post('/apply', [
            'leave_type_id' => $this->annual->id,
            'start_date' => $date,
            'end_date' => $date,
            'reason' => 'Sanitized display name',
            'attachment' => $file,
        ])->assertRedirect('/requests');

        $record = LeaveRequest::where('user_id', $this->owner->id)->latest('id')->firstOrFail();

        $this->assertLessThanOrEqual(120, strlen($record->attachment_original_name));
        $this->assertStringNotContainsString('/', $record->attachment_original_name);
        $this->assertStringNotContainsString('\\', $record->attachment_original_name);
        $this->assertStringNotContainsString(basename($record->attachment_path), $record->attachment_original_name);
    }

    public function test_details_pages_render_the_expected_preview_controls(): void
    {
        $employeePage = $this->actingAs($this->owner)
            ->get(route('requests.show', $this->leaveRequest));

        $employeePage->assertOk();
        $employeePage->assertSee('data-attachment-preview', false);
        $employeePage->assertSee('alt="photo.png"', false);
        $employeePage->assertSee('Download photo.png');

        $adminPage = $this->actingAs($this->admin)
            ->get(route('approvals.show', $this->leaveRequest));

        $adminPage->assertOk();
        $adminPage->assertSee('data-attachment-preview', false);

        $pdfPage = $this->actingAs($this->admin)
            ->get(route('approvals.show', $this->makePdfRequest()));

        $pdfPage->assertOk();
        $pdfPage->assertSee('target="_blank" rel="noopener"', false);
        $pdfPage->assertSee('Download note.pdf');
    }
}
