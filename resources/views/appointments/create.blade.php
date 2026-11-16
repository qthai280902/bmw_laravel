<x-app-layout>
    @php
        $rawType = old('type', request('type'));
        $currentTypeStr = $rawType === 'service'
            ? \App\Enums\AppointmentType::Maintenance->value
            : $rawType;
        $requestedType = \App\Enums\AppointmentType::tryFrom((string) $currentTypeStr);
        $isService = in_array($currentTypeStr, [
            \App\Enums\AppointmentType::Maintenance->value,
            \App\Enums\AppointmentType::Detailing->value,
            \App\Enums\AppointmentType::CarWash->value,
        ], true);
    @endphp

    <x-public.form-shell
        eyebrow="{{ $isService ? 'BMW Aftersales' : 'BMW Advisor' }}"
        title="{{ $requestedType?->label() ?? 'Đăng ký dịch vụ' }}"
        description="Gửi thông tin để showroom chuẩn bị mẫu xe, cấu hình hoặc lịch chăm sóc phù hợp trước khi cố vấn liên hệ xác nhận."
        class="border-t border-zinc-900"
    >
        <livewire:booking-form-wizard />
    </x-public.form-shell>
</x-app-layout>
