{{-- Vonage brand touches: gradient backdrop behind the login card. --}}
<style>
    .fi-simple-layout {
        background: url('{{ asset('images/vonage-gradient.jpg') }}') center / cover no-repeat fixed;
    }

    .fi-simple-layout .fi-simple-main {
        box-shadow: 0 24px 64px -24px rgb(0 0 0 / 0.45);
    }
</style>
<style>
    .vonage-sidebar-footer {
        padding: 1rem 1.5rem;
        font-size: 0.75rem;
        line-height: 1rem;
        color: var(--gray-500);
    }

    .vonage-sidebar-footer a {
        font-weight: 500;
        color: var(--primary-600);
    }

    .dark .vonage-sidebar-footer a {
        color: var(--primary-400);
    }

    .vonage-sidebar-footer a:hover {
        text-decoration: underline;
    }
</style>
