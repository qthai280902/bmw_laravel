@extends('emails.layouts.bmw-concierge')

@section('title', $rendered['subject'])
@section('accent_color', $rendered['accent_color'])
@section('header_subtitle', $rendered['eyebrow'])

@section('badge')
    <span style="display: inline-block; padding: 6px 14px; background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 9999px; color: {{ $rendered['accent_color'] }}; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
        {{ $rendered['category_label'] }}
    </span>
@endsection

@section('content')
    <h1 style="margin: 0 0 14px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
        {{ $rendered['heading'] }}
    </h1>

    @foreach($rendered['paragraphs'] as $paragraph)
        <p style="margin: 0 0 16px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #334155; line-height: 1.65;">
            {!! nl2br(e($paragraph)) !!}
        </p>
    @endforeach

    @if($rendered['highlight_title'] !== '' || $rendered['highlight_content'] !== '')
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 22px 0; background-color: #F8FAFC; border: 1px solid #E2E8F0; border-left: 4px solid {{ $rendered['accent_color'] }}; border-radius: 10px;">
            <tr>
                <td style="padding: 16px 18px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                    @if($rendered['highlight_title'] !== '')
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: {{ $rendered['accent_color'] }}; margin-bottom: 6px;">
                            {{ $rendered['highlight_title'] }}
                        </div>
                    @endif
                    @if($rendered['highlight_content'] !== '')
                        <div style="font-size: 14px; color: #0F172A; line-height: 1.6;">
                            {!! nl2br(e($rendered['highlight_content'])) !!}
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    @endif

    @if($rendered['cta_label'] !== '' && $rendered['cta_url'] !== '')
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 24px;">
            <tr>
                <td align="left">
                    <a href="{{ $rendered['cta_url'] }}"
                       style="display: inline-block; background-color: {{ $rendered['accent_color'] }}; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 13px; font-weight: 700; letter-spacing: 0.4px; border-radius: 10px;">
                        {{ $rendered['cta_label'] }}
                    </a>
                </td>
            </tr>
        </table>
    @endif
@endsection
