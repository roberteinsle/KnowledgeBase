@props(['space', 'size' => 'size-6'])
{{-- Uploaded image → emoji → initials badge --}}
@if ($space->hasImage())
    <img src="{{ route('spaces.icon', $space) }}?v={{ $space->updated_at?->timestamp }}" alt="" {{ $attributes->merge(['class' => $size.' shrink-0 rounded-md object-cover']) }}>
@elseif ($space->icon)
    <span aria-hidden="true" {{ $attributes->merge(['class' => $size.' grid shrink-0 place-items-center leading-none']) }}>{{ $space->icon }}</span>
@else
    <span aria-hidden="true" {{ $attributes->merge(['class' => $size.' '.$space->badgeColor().' grid shrink-0 place-items-center rounded-full text-[0.6rem] font-semibold text-white']) }}>{{ $space->initials() }}</span>
@endif
