BMW SHOWROOM VIETNAM — {{ mb_strtoupper($rendered['eyebrow'], 'UTF-8') }}
====================================================================

{{ $rendered['heading'] }}

{{ $rendered['body_content'] }}
@if($rendered['highlight_title'] !== '' || $rendered['highlight_content'] !== '')

---
@if($rendered['highlight_title'] !== ''){{ $rendered['highlight_title'] }}
@endif
{{ $rendered['highlight_content'] }}
---
@endif
@if($rendered['cta_label'] !== '' && $rendered['cta_url'] !== '')

{{ $rendered['cta_label'] }}: {{ $rendered['cta_url'] }}
@endif

Quý khách có thể bấm Trả lời (Reply) trực tiếp email này hoặc gọi Hotline BMW Showroom: 1900 123 456.

Trân trọng,
BMW Showroom Vietnam
