<x-app-layout title="Add user">

    <x-page-header
        title="Add user"
        subtitle="Create an account and assign its role."
        icon="bi-person-plus" />

    <div class="row">
        <div class="col-xl-9">
            <div class="card border-0">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('users.store') }}" novalidate>
                        @csrf

                        @include('users.form', ['user' => null])

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('users.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Create user
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
