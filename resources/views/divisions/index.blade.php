@extends('layouts.app')

@section('title', 'Peta Divisi')

@section('content')
    <div class="org-header">
        <div>
            <span class="org-label">STRUKTUR ORGANISASI</span>
            <h2 class="org-title">Peta Divisi</h2>
            <p class="org-description">Jelajahi struktur tim dan pantau persebaran laptop di seluruh perusahaan</p>
        </div>
        <a class="button button-primary" href="{{ route('divisions.create') }}">＋ Tambah divisi</a>
    </div>

    {{-- Company Overview Card --}}
    <div class="company-overview">
        <div class="company-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="4" y="2" width="16" height="20" rx="2"/>
                <path d="M9 6h6M9 10h6M9 14h6M9 18h6"/>
            </svg>
        </div>
        <div class="company-info">
            <div class="company-name">
                <strong>COMPANY OVERVIEW</strong>
                <h3>Ringkasan Perusahaan</h3>
                <p>Ikhtisar data dan inventaris dalam satu data</p>
            </div>
            <div class="company-stats">
                <div class="stat-item">
                    <strong>{{ $divisions->total() }}</strong>
                    <span>Divisi</span>
                </div>
                <div class="stat-item">
                    <strong>{{ $divisions->sum('users_count') }}</strong>
                    <span>Karyawan</span>
                </div>
                <div class="stat-item">
                    <strong>{{ $divisions->sum('laptops_count') }}</strong>
                    <span>Laptop</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Organizational Map --}}
    <div class="org-map-container">
        @forelse ($divisions as $index => $division)
            @php
                $colors = [
                    ['card' => '#f0f7ff', 'border' => '#b3d9ff', 'icon' => '#2563eb'],
                    ['card' => '#f0fdf4', 'border' => '#bbf7d0', 'icon' => '#16a34a'],
                    ['card' => '#fff7ed', 'border' => '#fed7aa', 'icon' => '#ea580c'],
                    ['card' => '#fdf4ff', 'border' => '#e9d5ff', 'icon' => '#9333ea'],
                    ['card' => '#ecfeff', 'border' => '#a5f3fc', 'icon' => '#06b6d4'],
                    ['card' => '#fef3c7', 'border' => '#fcd34d', 'icon' => '#d97706'],
                    ['card' => '#ede9fe', 'border' => '#c4b5fd', 'icon' => '#7c3aed'],
                ];
                $colorIndex = $index % count($colors);
                $color = $colors[$colorIndex];
            @endphp

            {{-- Connecting Line from Company Overview (only for first division) --}}
            @if ($index === 0)
                <div class="org-connector-start"></div>
            @endif

            {{-- Division Card with Branch Line --}}
            <div class="division-branch">
                <div class="branch-line"></div>
                <div class="division-card" style="background-color: {{ $color['card'] }}; border-color: {{ $color['border'] }};">
                    <div class="division-icon" style="background-color: {{ $color['icon'] }};">
                        @php
                            $icons = ['👔', '💼', '💰', '👥', '💻', '📢', '⚙️', '🎯', '📊', '🔧'];
                            echo $icons[$index % count($icons)];
                        @endphp
                    </div>
                    <div class="division-content">
                        <h4>{{ $division->name }}</h4>
                        <div class="division-metrics">
                            <div class="metric">
                                <span class="metric-icon">👤</span>
                                <span class="metric-value">{{ $division->users_count }} Karyawan</span>
                            </div>
                            <div class="metric">
                                <span class="metric-icon">💻</span>
                                <span class="metric-value">{{ $division->laptops_count }} laptop</span>
                            </div>
                        </div>
                        <div class="division-actions">
                            <a href="{{ route('divisions.edit', $division) }}" class="action-btn action-edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Ubah
                            </a>
                            <form method="POST" action="{{ route('divisions.destroy', $division) }}" onsubmit="return confirm('Hapus divisi {{ $division->name }}?')" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-btn action-delete">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    </svg>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Expand indicator --}}
                    @if ($division->sections && count($division->sections) > 0)
                        <button class="expand-toggle" onclick="this.closest('.division-card').classList.toggle('expanded')">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                    @endif
                </div>

                {{-- Sections (Bagian/Jabatan) - Hidden by default --}}
                @if ($division->sections && count($division->sections) > 0)
                    <div class="sections-container">
                        <div class="section-connector"></div>
                        <div class="sections-grid">
                            @foreach ($division->sections as $section)
                                <div class="section-card">
                                    <div class="section-dot"></div>
                                    <span>{{ $section }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="empty-org-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M3 9h18M9 21V9"/>
                </svg>
                <p>Belum ada divisi</p>
                <a href="{{ route('divisions.create') }}" class="button button-primary">＋ Tambah divisi pertama</a>
            </div>
        @endforelse
    </div>

    {{-- Info Footer --}}
    @if ($divisions->count() > 0)
        <div class="org-footer">
            <div class="org-info-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4M12 8h.01"/>
                </svg>
                <span>Klik "↓" untuk melihat inventaris dalam kategori {{ $divisions->total() }} divisi</span>
            </div>
            <div class="pagination-wrap">{{ $divisions->links() }}</div>
        </div>
    @endif
@endsection
