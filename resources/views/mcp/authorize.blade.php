{{-- OAuth consent screen (Passport) for MCP clients such as claude.ai. --}}
@php($target = parse_url((string) $request->redirect_uri, PHP_URL_HOST) ?: (string) $request->redirect_uri)
<x-layouts.guest :title="__('Connect :app', ['app' => $client->name])">
    <div class="text-center">
        <x-kb-icon name="link" class="mx-auto mb-3 size-10 text-indigo-600" />
        <h2 class="mb-2 text-lg font-semibold">{{ __(':app wants to access your knowledge base', ['app' => $client->name]) }}</h2>
        <p class="mb-4 text-sm text-slate-600">{{ __('Signed in as :name (:email).', ['name' => $user->name, 'email' => $user->email]) }}</p>
    </div>

    <div class="mb-4 rounded-md border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
        <p class="mb-2 font-medium">{{ __('The app will be able to:') }}</p>
        <ul class="list-disc space-y-1 pl-5">
            <li>{{ __('search and read the pages of all spaces you can read') }}</li>
            <li>{{ __('create, change and move pages where you are an editor') }}</li>
            @if ($user->isAdmin())
                <li>{{ __('create and change spaces (you are an administrator)') }}</li>
            @endif
        </ul>
        <p class="mt-3 text-xs text-slate-500">{{ __('It acts with your permissions. Every change is versioned and recorded in the activity log. You can revoke access at any time under Account.') }}</p>
    </div>

    <p class="mb-4 text-center text-xs text-slate-500">{{ __('After approval you will be sent back to :host.', ['host' => $target]) }}</p>

    <div class="flex gap-2">
        <form method="post" action="{{ route('passport.authorizations.deny') }}" class="flex-1">
            @csrf @method('DELETE')
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn-secondary w-full">{{ __('Deny') }}</button>
        </form>
        <form method="post" action="{{ route('passport.authorizations.approve') }}" class="flex-1">
            @csrf
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn-primary w-full">{{ __('Allow') }}</button>
        </form>
    </div>
</x-layouts.guest>
