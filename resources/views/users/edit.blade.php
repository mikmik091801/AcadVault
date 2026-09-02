<x-app-layout title="Edit user">

    <x-page-header
        title="Edit user"
        subtitle="{{ $user->name }} · {{ $user->email }}"
        icon="bi-person-gear" />

    <div class="row">
        <div class="col-xl-9">
            <div class="card border-0">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('users.update', $user) }}" novalidate>
                        @csrf
                        @method('put')

                        @include('users.form', ['user' => $user])

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('users.show', $user) }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1" aria-hidden="true"></i>Save changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
