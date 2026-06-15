# AI Assistant Architecture

## Phase 16 Overview

The public BMW Showroom AI Assistant is implemented with Laravel AI SDK.

Primary pieces:

- Agent: `App\Ai\Agents\ShowroomAssistant`.
- Service: `App\Services\Ai\ShowroomAssistantService`.
- Controller: `App\Http\Controllers\Ai\ShowroomAssistantController`.
- Route: `POST /ai/showroom-assistant`.
- Widget: `resources/views/components/public/ai-assistant-widget.blade.php`.
- Avatar: `resources/views/components/public/ai-assistant-avatar.blade.php`.

## Provider Configuration

- Default assistant provider: `AI_ASSISTANT_PROVIDER`, fallback `gemini`.
- Gemini API key: `GEMINI_API_KEY`.
- Optional model override: `AI_ASSISTANT_MODEL`.
- Assistant enabled flag: `AI_ASSISTANT_ENABLED`.

No API key is hard-coded in source code.

## Prompt Context Boundary

Allowed public context:

- Active public products.
- Product category/type/specifications/public URLs.
- Published public articles.

Excluded private context:

- Appointments.
- Accessory orders.
- Users.
- Customer names, phones, emails and addresses.
- Admin notes or internal notes.
- Draft articles.

## Runtime Behavior

- Missing key returns configured fallback message.
- Provider exception returns configured fallback message and logs only exception class.
- Endpoint validates message as required string max 600 chars.
- Endpoint is rate limited with `throttle:12,1`.

## Widget Behavior

- Public layout only.
- Admin layout does not render the public widget.
- Desktop starts with the panel open.
- Mobile starts with compact launcher/greeting.
- Drag movement is handled by Alpine pointer events.
- Reduced-motion users do not receive assistant avatar animation.
