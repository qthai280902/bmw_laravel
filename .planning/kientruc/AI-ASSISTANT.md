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
- Product line and normalized search aliases.
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
- Phase 16.2 changes:
  - fixed launcher/panel/side-tab pattern replaces drag-positioned UX.
  - no drag handle and no persisted coordinate state.
  - greeting text is an intro card, not a stored assistant message.
  - state key is `bmw_ai_assistant_state_v4`.
  - legacy `bmw_ai_assistant_state_v2`, `bmw_ai_assistant_state_v3` and `bmw_ai_assistant_position` keys are cleared.
  - safe assistant rendering uses text blocks and internal action chips.
  - action chip labels map internal URLs to clear actions such as product detail, quote, test drive, compare, accessory order and BMW Motorrad catalog.
  - BMW S1000RR and model-code queries are supported through normalized aliases.
  - mobile panel width is constrained to stay inside 390px viewports.
- Phase 16.1 changes:
  - fresh desktop and mobile start with compact launcher/greeting.
  - panel opens only when the user clicks the launcher or side-tab.
  - side-tab hidden mode persists through reload.
  - recent messages persist through navigation/reload in `localStorage`.
  - stored messages are capped at 24.
  - email/phone-like contact data is redacted before local storage.
  - conversation history is not sent back to the AI endpoint.
  - clear conversation action resets local chat state.
- Reduced-motion users do not receive assistant avatar animation.
- Markdown rendering is token-based and escaped with `x-text`; the widget does not use `x-html`.

## Product Context Intelligence

- `ShowroomAssistantService::publicContext()` accepts the current message so products can be scored by relevance.
- Context product query loads active products with categories and caps at at least 30 items.
- Product matching uses normalized aliases from:
  - product name.
  - slug.
  - category name.
  - product line.
  - useful model-code tokens containing digits.
- Examples now covered:
  - `330i`.
  - `530i`.
  - `x5`.
  - `S1000RR`.
  - `BMW S 1000 RR`.
