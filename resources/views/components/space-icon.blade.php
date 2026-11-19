@props(['space', 'size' => 'size-6'])
{{-- Uploaded image, else a generated avatar (emoji or initials) – the same look as user avatars. --}}
<img src="{{ $space->iconUrl() }}" alt="" loading="lazy" {{ $attributes->merge(['class' => $size.' shrink-0 rounded-md object-cover']) }}>
