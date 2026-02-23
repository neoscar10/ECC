<nav class="pavilion-bottom-nav">
    <div class="pavilion-container d-flex justify-content-around align-items-center">
        <a href="{{ route('home') }}" class="nav-item-pavilion {{ Route::is('home') ? 'active' : '' }}">
            <i class="material-symbols-outlined">{{ Route::is('home') ? 'explore' : 'explore' }}</i>
            <span>Explore</span>
        </a>

        <a href="#" class="nav-item-pavilion">
            <i class="material-symbols-outlined">collections</i>
            <span>Collections</span>
            <span class="material-symbols-outlined locked-badge">lock</span>
        </a>

        <a href="#" class="nav-item-pavilion">
            <i class="material-symbols-outlined">account_balance_wallet</i>
            <span>Invest</span>
            <span class="material-symbols-outlined locked-badge">lock</span>
        </a>

        <a href="#" class="nav-item-pavilion">
            <i class="material-symbols-outlined">person</i>
            <span>Profile</span>
        </a>
    </div>
</nav>
