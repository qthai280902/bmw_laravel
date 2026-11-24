<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\Mail\EmailTemplateRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));
        $status = trim((string) $request->query('status', ''));

        $templates = EmailTemplate::query()
            ->with(['creator:id,name', 'updater:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists($category, EmailTemplate::categoryOptions()), fn ($q) => $q->where('category', $category))
            ->when(array_key_exists($status, EmailTemplate::statusOptions()), fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'draft' THEN 1 ELSE 2 END")
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        $statsRow = EmailTemplate::query()
            ->toBase()
            ->selectRaw(
                'COUNT(*) as total_count,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_count,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as draft_count,
                 SUM(CASE WHEN category = ? THEN 1 ELSE 0 END) as installment_count',
                [
                    EmailTemplate::STATUS_ACTIVE,
                    EmailTemplate::STATUS_DRAFT,
                    EmailTemplate::CATEGORY_INSTALLMENT,
                ]
            )
            ->first();

        return view('admin.email-templates.index', [
            'templates' => $templates,
            'search' => $search,
            'category' => $category,
            'status' => $status,
            'categoryOptions' => EmailTemplate::categoryOptions(),
            'statusOptions' => EmailTemplate::statusOptions(),
            'stats' => [
                'total' => (int) ($statsRow->total_count ?? 0),
                'active' => (int) ($statsRow->active_count ?? 0),
                'draft' => (int) ($statsRow->draft_count ?? 0),
                'installment' => (int) ($statsRow->installment_count ?? 0),
            ],
        ]);
    }

    public function create(): View
    {
        $template = new EmailTemplate([
            'category' => EmailTemplate::CATEGORY_PROMOTION,
            'status' => EmailTemplate::STATUS_ACTIVE,
            'accent_color' => '#1C69D4',
            'eyebrow' => 'BMW Client Relations',
        ]);

        return view('admin.email-templates.create', [
            'template' => $template,
            'categoryOptions' => EmailTemplate::categoryOptions(),
            'statusOptions' => EmailTemplate::statusOptions(),
            'variableCatalog' => EmailTemplateRenderer::variableCatalog(),
            'sampleVariables' => EmailTemplateRenderer::sampleVariables(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTemplate($request);

        $code = trim((string) ($validated['code'] ?? ''));
        if ($code === '') {
            $baseCode = Str::slug((string) $validated['name'], '-');
            $code = $this->uniqueCode($baseCode !== '' ? $baseCode : 'bmw-email-template');
        }

        $template = EmailTemplate::query()->create([
            ...$validated,
            'code' => $code,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.email-templates.edit', $template)
            ->with('success', "Đã tạo mẫu Email \"{$template->name}\" thành công.");
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        $emailTemplate->loadMissing(['creator:id,name', 'updater:id,name']);

        return view('admin.email-templates.edit', [
            'template' => $emailTemplate,
            'categoryOptions' => EmailTemplate::categoryOptions(),
            'statusOptions' => EmailTemplate::statusOptions(),
            'variableCatalog' => EmailTemplateRenderer::variableCatalog(),
            'sampleVariables' => EmailTemplateRenderer::sampleVariables(),
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $validated = $this->validateTemplate($request, $emailTemplate->id);

        $emailTemplate->update([
            ...$validated,
            'code' => filled($validated['code'] ?? null) ? trim((string) $validated['code']) : $emailTemplate->code,
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.email-templates.edit', $emailTemplate)
            ->with('success', "Đã cập nhật mẫu Email \"{$emailTemplate->name}\".");
    }

    public function duplicate(EmailTemplate $emailTemplate): RedirectResponse
    {
        $copy = $emailTemplate->replicate();
        $copy->code = $this->uniqueCode($emailTemplate->code.'-copy');
        $copy->name = Str::limit($emailTemplate->name, 165, '').' (Bản sao)';
        $copy->status = EmailTemplate::STATUS_DRAFT;
        $copy->created_by = Auth::id();
        $copy->updated_by = Auth::id();
        $copy->save();

        return redirect()
            ->route('admin.email-templates.edit', $copy)
            ->with('success', "Đã nhân bản mẫu Email sang bản nháp \"{$copy->name}\".");
    }

    public function updateStatus(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(EmailTemplate::statusOptions()))],
        ]);

        $emailTemplate->update([
            'status' => $validated['status'],
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', "Đã chuyển trạng thái mẫu \"{$emailTemplate->name}\" sang {$emailTemplate->statusLabel()}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTemplate(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'code' => [
                'nullable',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('email_templates', 'code')->ignore($ignoreId),
            ],
            'category' => ['required', 'string', Rule::in(array_keys(EmailTemplate::categoryOptions()))],
            'status' => ['required', 'string', Rule::in(array_keys(EmailTemplate::statusOptions()))],
            'subject' => ['required', 'string', 'max:220'],
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'heading' => ['required', 'string', 'max:220'],
            'body_content' => ['required', 'string', 'max:10000'],
            'highlight_title' => ['nullable', 'string', 'max:180'],
            'highlight_content' => ['nullable', 'string', 'max:3000'],
            'cta_label' => ['nullable', 'string', 'max:120'],
            'cta_url' => ['nullable', 'url', 'max:255'],
            'accent_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], [
            'name.required' => 'Vui lòng nhập tên mẫu Email.',
            'code.regex' => 'Mã mẫu chỉ gồm chữ thường không dấu, số và dấu gạch ngang (ví dụ: nhac-tra-gop).',
            'code.unique' => 'Mã mẫu Email này đã tồn tại.',
            'subject.required' => 'Vui lòng nhập tiêu đề Email.',
            'heading.required' => 'Vui lòng nhập tiêu đề chính trong nội dung thư.',
            'body_content.required' => 'Vui lòng nhập nội dung Email.',
            'cta_url.url' => 'Đường dẫn CTA phải là URL hợp lệ (bắt đầu bằng http:// hoặc https://).',
            'accent_color.regex' => 'Màu nhấn phải có định dạng mã màu HEX (ví dụ: #1C69D4).',
        ]);
    }

    private function uniqueCode(string $base): string
    {
        $slug = Str::slug($base, '-');
        if ($slug === '') {
            $slug = 'bmw-email-template';
        }
        $slug = Str::limit($slug, 105, '');
        $candidate = $slug;
        $counter = 1;

        while (EmailTemplate::query()->where('code', $candidate)->exists()) {
            $candidate = "{$slug}-{$counter}";
            $counter++;
        }

        return $candidate;
    }
}
