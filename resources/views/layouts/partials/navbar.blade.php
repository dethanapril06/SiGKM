@php
    $user = auth()->user();
    $avatarUrl =
        'https://ui-avatars.com/api/?' .
        http_build_query([
            'name' => $user->name,
            'background' => '696cff',
            'color' => 'ffffff',
            'bold' => true,
        ]);
    $unreadNotificationCount = $user->unreadNotifications()->count();
    $navbarNotifications = $user->notifications()->latest()->limit(5)->get();
@endphp

<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

        <div class="navbar-nav align-items-center">
            <form action="{{ route('global-search') }}" method="GET" class="nav-item d-flex align-items-center mb-0">
                <i class="bx bx-search fs-4 lh-0 me-2 text-muted"></i>
                <input type="text" name="q" class="form-control border-0 shadow-none" placeholder="Cari data (MK, Temuan, RTL, RTM)..."
                    aria-label="Cari data..." value="{{ request('q') }}" />
            </form>
        </div>

        <ul class="navbar-nav flex-row align-items-center ms-auto">

            <a class="nav-link active dropdown-toggle text-gray-600" href="#" data-bs-toggle="dropdown"
                data-bs-display="static" aria-expanded="false">
                <i class="bx bx-bell fs-4"></i>
                {{-- Badge hanya muncul kalau ada notif belum dibaca --}}
                @if ($unreadNotificationCount > 0)
                    <span class="badge badge-notification bg-danger">
                        {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                    </span>
                @endif
            </a>
            <ul class="dropdown-menu dropdown-center dropdown-menu-sm-end notification-dropdown"
                aria-labelledby="dropdownMenuButton">
                <li class="dropdown-header">
                    <h6>Notifikasi</h6>
                </li>

                {{-- Loop notifikasi --}}
                @forelse($navbarNotifications as $notif)
                    <li class="dropdown-item notification-item {{ $notif->read_at ? '' : 'bg-label-primary' }}">
                        <a class="d-flex align-items-center" href="{{ route('notifications.read', $notif) }}">
                            <div class="notification-icon bg-{{ $notif->data['color'] ?? 'primary' }}">
                                <i class="bx {{ $notif->data['icon'] ?? 'bx-bell' }}"></i>
                            </div>
                            <div class="notification-text ms-4">
                                <p class="notification-title font-bold">
                                    {{ $notif->data['title'] ?? 'Notifikasi' }}
                                </p>
                                <p class="notification-subtitle font-thin text-sm">
                                    {{ $notif->data['pesan'] ?? '-' }}
                                </p>
                                <p class="notification-subtitle font-thin text-sm text-muted">
                                    {{ $notif->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="dropdown-item text-center text-muted">
                        Tidak ada notifikasi baru
                    </li>
                @endforelse

                <li>
                    <p class="text-center py-2 mb-0">
                        <a href="{{ route('notifications.index') }}">Lihat Semua Notifikasi</a>
                    </p>
                </li>
            </ul>

            <li class="nav-item me-3">
                <span class="badge bg-label-primary">
                    {{ $user->role->name }}
                </span>
            </li>

            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="{{ $avatarUrl }}" alt="{{ $user->name }}"
                            class="w-px-40 h-auto rounded-circle" />
                    </div>
                </a>

                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="{{ $avatarUrl }}" alt="{{ $user->name }}"
                                            class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block">
                                        {{ $user->name }}
                                    </span>
                                    <small class="text-muted">
                                        {{ $user->role->name }}
                                    </small>
                                </div>
                            </div>
                        </a>
                    </li>

                    <li>
                        <div class="dropdown-divider"></div>
                    </li>

                    <li>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="bx bx-user me-2"></i>
                            <span class="align-middle">Profil Saya</span>
                        </a>
                    </li>

                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button type="submit" class="dropdown-item">
                                <i class="bx bx-power-off me-2"></i>
                                <span class="align-middle">Logout</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
