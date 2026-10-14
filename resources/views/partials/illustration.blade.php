{{-- Hero illustration: a morphing blob with currency signs, a coin stack and a drone delivering a coin. --}}
<div class="art" aria-hidden="true">
    <div class="art-blob"></div>
    <div class="art-blob art-blob-small"></div>
    <span class="art-sign art-euro">€</span>
    <span class="art-sign art-dollar">$</span>

    <svg class="art-drone" viewBox="0 0 220 210">
        <g class="drone-body">
            <path d="M40 46 110 62 180 46" fill="none" stroke="#222" stroke-width="5" stroke-linecap="round"/>
            <rect x="88" y="50" width="44" height="22" rx="6" fill="#fff" stroke="#222" stroke-width="4"/>
            <circle cx="110" cy="61" r="4" fill="#222"/>
            <g class="prop prop-left"><ellipse cx="40" cy="40" rx="34" ry="9" fill="#fff" stroke="#222" stroke-width="3"/><ellipse cx="40" cy="40" rx="20" ry="4" fill="none" stroke="#fdcb2e" stroke-width="3"/></g>
            <g class="prop prop-right"><ellipse cx="180" cy="40" rx="34" ry="9" fill="#fff" stroke="#222" stroke-width="3"/><ellipse cx="180" cy="40" rx="20" ry="4" fill="none" stroke="#fdcb2e" stroke-width="3"/></g>
            <path d="M98 72 92 122M122 72 128 122" stroke="#222" stroke-width="2.5"/>
            <g class="drone-coin">
                <ellipse cx="110" cy="152" rx="30" ry="36" fill="#fff" stroke="#222" stroke-width="4" transform="rotate(25 110 152)"/>
                <ellipse cx="114" cy="150" rx="18" ry="24" fill="#fdcb2e" stroke="#222" stroke-width="3" transform="rotate(25 114 150)"/>
                <path d="M103 136l16 30M110 132l16 30" stroke="#fff" stroke-width="4" transform="rotate(10 114 150)"/>
            </g>
        </g>
    </svg>

    <svg class="art-stack" viewBox="0 0 260 150">
        @foreach ([0, 1, 2, 3, 4] as $i)
            <g class="stack-coin" style="--i: {{ $i }}">
                <rect x="20" y="{{ 104 - $i * 18 }}" width="130" height="20" rx="4" fill="#fff" stroke="#222" stroke-width="3.5"/>
                <path d="@for ($x = 32; $x < 150; $x += 9)M{{ $x }} {{ 108 - $i * 18 }}v12 @endfor" stroke="#222" stroke-width="2.5"/>
            </g>
        @endforeach
        <g class="rolling-coin">
            <circle cx="200" cy="96" r="44" fill="#fff" stroke="#222" stroke-width="4"/>
            <circle cx="200" cy="96" r="32" fill="#fdcb2e" stroke="#222" stroke-width="3.5"/>
            <path d="M186 74l26 44M196 70l26 44" stroke="#fff" stroke-width="6"/>
        </g>
        <path d="M6 126h244" stroke="#222" stroke-width="3" stroke-linecap="round"/>
    </svg>

    <div class="art-card">
        <span class="art-card-dot"></span>
        <div><b>Groceries</b><small>78% of budget</small></div>
        <span class="art-card-bar"><span></span></span>
    </div>
</div>
