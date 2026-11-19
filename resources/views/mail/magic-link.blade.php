<x-mail::message>
# {{ __('Sign in to :name', ['name' => $workspace]) }}

{{ __('Click the button to sign in. The link is valid for :minutes minutes and works only once.', ['minutes' => $minutes]) }}

<x-mail::button :url="$url">
{{ __('Sign in') }}
</x-mail::button>

{{ __('If you did not request this e-mail, you can ignore it.') }}
</x-mail::message>
