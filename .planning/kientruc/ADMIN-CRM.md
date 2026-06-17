# Admin CRM Architecture

## Admin Shell

- Main layout: `resources/views/components/admin-layout.blade.php`.
- Shared admin components:
  - `resources/views/components/admin/card.blade.php`
  - `resources/views/components/admin/page-header.blade.php`
  - `resources/views/components/admin/sidebar-link.blade.php`
  - `resources/views/components/admin/badge.blade.php`

## AI Conversations Module

- Controller: `App\Http\Controllers\Admin\AiConversationController`.
- Routes:
  - `GET /admin/ai-conversations`
  - `GET /admin/ai-conversations/{session}`
  - `PATCH /admin/ai-conversations/{session}/status`
- Views:
  - `resources/views/admin/ai-conversations/index.blade.php`
  - `resources/views/admin/ai-conversations/show.blade.php`
- Sidebar label: `Lịch sử trợ lý AI`.

## Customer Display Rules

- If an AI session is linked to a known customer, admin displays customer name.
- If unknown, admin displays a masked IP label.
- Original IP metadata is preserved in the database.
- Visitor IDs are masked in admin UI.

## Linking Rules

- Visitor ID is the primary linking key.
- IP fallback is allowed only for one recent unlinked session.
- IP fallback confidence is stored as `ip_recent`.
