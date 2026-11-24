{{-- Store buttons. Expects $appLinks (['play' => url, 'ios' => url]) and optional $small. --}}
<div class="stores {{ ! empty($small) ? 'sm' : '' }}">
    @isset($appLinks['play'])
        <a class="store" href="{{ $appLinks['play'] }}" target="_blank" rel="noopener" aria-label="Google Play থেকে ডাউনলোড করুন">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#34a853" d="M4 2.6 13.6 12 4 21.4c-.4-.2-.6-.6-.6-1.1V3.7c0-.5.2-.9.6-1.1z"/><path fill="#fbbc04" d="m16.8 8.8-3.2 3.2 3.2 3.2 3.6-2c.9-.5.9-1.9 0-2.4z"/><path fill="#ea4335" d="M13.6 12 4 21.4c.3.2.8.2 1.2 0l11.6-6.2z"/><path fill="#4285f4" d="M13.6 12 16.8 8.8 5.2 2.6c-.4-.2-.9-.2-1.2 0z"/></svg>
            <span><small>GET IT ON</small>Google Play</span>
        </a>
    @endisset
    @isset($appLinks['ios'])
        <a class="store" href="{{ $appLinks['ios'] }}" target="_blank" rel="noopener" aria-label="App Store থেকে ডাউনলোড করুন">
            <svg viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M16.4 12.6c0-2.5 2-3.7 2.1-3.8-1.2-1.7-3-1.9-3.6-2-1.5-.2-3 .9-3.8.9-.8 0-2-.9-3.3-.9-1.7 0-3.3 1-4.2 2.6-1.8 3.1-.5 7.7 1.3 10.2.9 1.2 1.9 2.6 3.2 2.6 1.3-.1 1.8-.8 3.3-.8s2 .8 3.3.8c1.4 0 2.3-1.3 3.1-2.5 1-1.4 1.4-2.8 1.4-2.9 0 0-2.8-1-2.8-4.2zM13.9 5.1c.7-.8 1.2-2 1-3.1-1 0-2.2.7-2.9 1.5-.6.7-1.2 1.9-1 3 1.1.1 2.2-.6 2.9-1.4z"/></svg>
            <span><small>Download on the</small>App Store</span>
        </a>
    @endisset
</div>
