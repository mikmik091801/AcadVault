<x-app-layout title="Profile">

    <x-page-header
        title="Profile settings"
        subtitle="Manage your account details, password and session."
        icon="bi-person-gear" />

    <div class="row g-4">
        <div class="col-lg-7">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="col-lg-5">
            @include('profile.partials.update-password-form')
        </div>

        <div class="col-12">
            @include('profile.partials.delete-user-form')
        </div>
    </div>

</x-app-layout>
