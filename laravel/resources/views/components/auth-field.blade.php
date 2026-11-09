@props(['name', 'label', 'type' => 'text', 'autocomplete' => null, 'required' => true])

<div>
    <label for="{{ $name }}" class="mb-2 block text-sm font-medium text-slate-800">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name) }}" @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif @if ($required) required @endif {{ $attributes->merge(['class' => 'block w-full rounded-xl border border-slate-200 bg-white px-4 py-3.5 text-sm text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100']) }} aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" @if ($errors->has($name)) aria-describedby="{{ $name }}-error" @endif>
    @error($name)<p id="{{ $name }}-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
</div>
