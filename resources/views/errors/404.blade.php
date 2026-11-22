@extends('layouts.public')

@section('title', '404 - Page Not Found')

@section('content')
<div class="error-404-page" role="main" aria-label="Page not found">
    <div class="container">
        <header class="error-header">
            <h1 class="error-brand">eatalyft.</h1>
            <p class="error-question">Is the page really here?</p>
            <h2 class="error-main-title">Nobody showed up here</h2>
        </header>

        <div class="error-illustration" aria-live="polite">
            <p class="error-subtitle">This page doesn't exist, but five friends came anyway and got lost. Seat them at the table, then let's go to pages that are real.</p>
            
            <div class="seating-area" role="region" aria-label="Seat your friends">
                <div class="table-area" id="table-area" role="list" aria-label="Table - 0 of 5 seated">
                    <div class="table-svg" aria-hidden="true">
                        <svg viewBox="0 0 300 180" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <ellipse cx="150" cy="140" rx="120" ry="25" stroke="currentColor" stroke-width="2" stroke-dasharray="5,5" opacity="0.3"/>
                            <ellipse cx="150" cy="140" rx="110" ry="20" fill="currentColor" opacity="0.1"/>
                            <rect x="140" y="80" width="20" height="60" rx="5" fill="currentColor" opacity="0.2"/>
                        </svg>
                    </div>
                    <div class="seated-friends" id="seated-friends" role="list"></div>
                    <p class="table-caption" id="table-caption">At the table: <span id="seated-count">0</span> of 5</p>
                </div>

                <div class="friends-pool" id="friends-pool" role="list" aria-label="Friends waiting to be seated">
                    <div class="friend-card" data-friend="1" role="listitem" tabindex="0" draggable="true" aria-label="Alex - Frontend Developer">
                        <div class="friend-avatar" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">A</div>
                        <span class="friend-name">Alex</span>
                        <span class="friend-role">Frontend Dev</span>
                    </div>
                    <div class="friend-card" data-friend="2" role="listitem" tabindex="0" draggable="true" aria-label="Maria - UX Designer">
                        <div class="friend-avatar" style="background: linear-gradient(135deg, #ec4899, #be185d);">M</div>
                        <span class="friend-name">Maria</span>
                        <span class="friend-role">UX Designer</span>
                    </div>
                    <div class="friend-card" data-friend="3" role="listitem" tabindex="0" draggable="true" aria-label="James - Backend Engineer">
                        <div class="friend-avatar" style="background: linear-gradient(135deg, #10b981, #047857);">J</div>
                        <span class="friend-name">James</span>
                        <span class="friend-role">Backend Eng</span>
                    </div>
                    <div class="friend-card" data-friend="4" role="listitem" tabindex="0" draggable="true" aria-label="Priya - Product Manager">
                        <div class="friend-avatar" style="background: linear-gradient(135deg, #f59e0b, #b45309);">P</div>
                        <span class="friend-name">Priya</span>
                        <span class="friend-role">Product Mgr</span>
                    </div>
                    <div class="friend-card" data-friend="5" role="listitem" tabindex="0" draggable="true" aria-label="Sam - DevOps Engineer">
                        <div class="friend-avatar" style="background: linear-gradient(135deg, #8b5cf6, #5b21b6);">S</div>
                        <span class="friend-name">Sam</span>
                        <span class="friend-role">DevOps</span>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary call-everyone" id="call-everyone" type="button" aria-label="Call everyone to the table">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                <span>Call everyone</span>
            </button>
        </div>

        <div class="error-details" role="region" aria-label="Error details">
            <div class="error-code" aria-hidden="true">404</div>
            
            <table class="meetup-table" role="table">
                <caption>The page on this route</caption>
                <tbody>
                    <tr>
                        <th scope="row">When</th>
                        <td>Never</td>
                    </tr>
                    <tr>
                        <th scope="row">Where</th>
                        <td><code>{{ request()->getRequestUri() }}</code></td>
                    </tr>
                    <tr>
                        <th scope="row">Guests</th>
                        <td><span id="guests-count">0</span> of 5</td>
                    </tr>
                    <tr>
                        <th scope="row">Host</th>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav class="error-navigation" aria-label="Quick navigation">
            <a href="{{ route('home') }}" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <span>Home</span>
            </a>
            <a href="{{ route('services.index') }}" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                <span>Services</span>
            </a>
            <a href="{{ route('about') }}" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span>About</span>
            </a>
            <a href="{{ route('contact') }}" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                <span>Contact</span>
            </a>
        </nav>
    </div>
</div>

<style>
.error-404-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    padding: calc(var(--space-8) + 60px) var(--space-4) var(--space-12);
}

.error-404-page .container {
    max-width: 720px;
    margin: 0 auto;
    text-align: center;
}

.error-header {
    margin-bottom: var(--space-10);
}

.error-brand {
    font-size: clamp(1.5rem, 4vw, 2.5rem);
    font-weight: 700;
    color: var(--color-primary);
    letter-spacing: -0.02em;
    margin-bottom: var(--space-3);
}

.error-question {
    font-size: clamp(1rem, 2.5vw, 1.25rem);
    color: var(--color-text-muted);
    margin-bottom: var(--space-4);
    font-weight: 400;
}

.error-main-title {
    font-size: clamp(1.75rem, 5vw, 3rem);
    font-weight: 700;
    color: var(--color-text);
    line-height: 1.2;
    letter-spacing: -0.03em;
}

.error-illustration {
    margin-bottom: var(--space-10);
}

.error-subtitle {
    font-size: clamp(0.95rem, 2vw, 1.1rem);
    color: var(--color-text-muted);
    max-width: 500px;
    margin: 0 auto var(--space-8);
    line-height: 1.6;
}

.seating-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-8);
}

.table-area {
    position: relative;
    width: 100%;
    max-width: 400px;
}

.table-svg {
    width: 100%;
    height: 180px;
    color: var(--color-border);
}

.seated-friends {
    position: absolute;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--space-2);
    min-height: 100px;
    width: 90%;
    pointer-events: none;
}

.seated-friend {
    pointer-events: auto;
    animation: seatFriend 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes seatFriend {
    from { opacity: 0; transform: translateY(-20px) scale(0.9); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.table-caption {
    margin-top: var(--space-4);
    font-size: 0.9rem;
    color: var(--color-text-muted);
    font-weight: 500;
}

.friends-pool {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--space-3);
    width: 100%;
}

.friend-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-3) var(--space-4);
    background: var(--color-surface);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-lg);
    cursor: grab;
    transition: all 0.2s ease;
    min-width: 100px;
    user-select: none;
}

.friend-card:hover {
    border-color: var(--color-primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.friend-card:active,
.friend-card.dragging {
    cursor: grabbing;
    transform: rotate(3deg) scale(1.05);
    box-shadow: var(--shadow-lg);
    z-index: 10;
}

.friend-card.seated {
    opacity: 0.4;
    pointer-events: none;
    transform: none;
}

.friend-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    box-shadow: var(--shadow-sm);
}

.friend-name {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--color-text);
}

.friend-role {
    font-size: 0.7rem;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.call-everyone {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-3) var(--space-6);
    font-size: 1rem;
    font-weight: 600;
    border-radius: var(--radius-full);
    transition: all 0.2s ease;
}

.call-everyone:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.call-everyone:active {
    transform: translateY(0);
}

.call-everyone .btn-icon {
    width: 20px;
    height: 20px;
}

.error-details {
    margin-bottom: var(--space-10);
}

.error-code {
    font-size: clamp(6rem, 20vw, 14rem);
    font-weight: 800;
    color: var(--color-border);
    line-height: 1;
    letter-spacing: -0.05em;
    margin-bottom: var(--space-6);
    opacity: 0.3;
}

.meetup-table {
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
    border-collapse: collapse;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.meetup-table caption {
    padding: var(--space-3);
    font-weight: 600;
    color: var(--color-text-muted);
    font-size: 0.875rem;
    text-align: left;
    background: var(--color-bg-elevated);
    border-bottom: 1px solid var(--color-border);
}

.meetup-table th,
.meetup-table td {
    padding: var(--space-3) var(--space-4);
    text-align: left;
    border-bottom: 1px solid var(--color-border);
}

.meetup-table th {
    width: 100px;
    font-weight: 500;
    color: var(--color-text-muted);
    font-size: 0.875rem;
}

.meetup-table td {
    font-weight: 500;
    color: var(--color-text);
    font-size: 0.875rem;
}

.meetup-table code {
    background: var(--color-bg);
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    font-family: var(--font-mono);
    font-size: 0.8rem;
    word-break: break-all;
}

.meetup-table tr:last-child th,
.meetup-table tr:last-child td {
    border-bottom: none;
}

.error-navigation {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--space-3);
    margin-top: var(--space-4);
}

.nav-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-3) var(--space-5);
    background: var(--color-surface);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-full);
    color: var(--color-text);
    font-weight: 500;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.nav-link:hover {
    border-color: var(--color-primary);
    color: var(--color-primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.nav-link svg {
    width: 18px;
    height: 18px;
}

@media (max-width: 640px) {
    .error-404-page {
        padding-top: calc(var(--space-6) + 60px);
    }
    
    .seated-friends {
        position: static;
        transform: none;
        margin-top: var(--space-4);
        min-height: auto;
    }
    
    .table-svg {
        height: 140px;
    }
    
    .friend-card {
        min-width: calc(50% - var(--space-3)/2);
        padding: var(--space-2) var(--space-3);
    }
    
    .friend-avatar {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }
    
    .error-code {
        font-size: clamp(4rem, 15vw, 8rem);
    }
    
    .nav-link {
        flex: 1;
        min-width: calc(50% - var(--space-3)/2);
        justify-content: center;
        padding: var(--space-2) var(--space-3);
        font-size: 0.8rem;
    }
    
    .nav-link span {
        display: none;
    }
    
    .nav-link svg {
        width: 22px;
        height: 22px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const friendCards = document.querySelectorAll('.friend-card[data-friend]');
    const seatedFriends = document.getElementById('seated-friends');
    const tableArea = document.getElementById('table-area');
    const seatedCount = document.getElementById('seated-count');
    const guestsCount = document.getElementById('guests-count');
    const tableCaption = document.getElementById('table-caption');
    const callEveryoneBtn = document.getElementById('call-everyone');
    const friendsPool = document.getElementById('friends-pool');

    let seatedCountValue = 0;
    const totalFriends = 5;

    // Friend data
    const friendsData = {
        1: { name: 'Alex', role: 'Frontend Dev', avatar: 'A', gradient: 'linear-gradient(135deg, #3b82f6, #1d4ed8)' },
        2: { name: 'Maria', role: 'UX Designer', avatar: 'M', gradient: 'linear-gradient(135deg, #ec4899, #be185d)' },
        3: { name: 'James', role: 'Backend Eng', avatar: 'J', gradient: 'linear-gradient(135deg, #10b981, #047857)' },
        4: { name: 'Priya', role: 'Product Mgr', avatar: 'P', gradient: 'linear-gradient(135deg, #f59e0b, #b45309)' },
        5: { name: 'Sam', role: 'DevOps', avatar: 'S', gradient: 'linear-gradient(135deg, #8b5cf6, #5b21b6)' }
    };

    function updateCounts() {
        seatedCount.textContent = seatedCountValue;
        guestsCount.textContent = seatedCountValue;
    }

    function createSeatedFriend(friendId) {
        const friend = friendsData[friendId];
        const el = document.createElement('div');
        el.className = 'seated-friend';
        el.dataset.friend = friendId;
        el.innerHTML = `
            <div class="friend-avatar" style="background: ${friend.gradient};">${friend.avatar}</div>
            <span class="friend-name">${friend.name}</span>
        `;
        el.style.cssText = `
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 8px 12px;
            background: var(--color-surface);
            border: 2px solid var(--color-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        `;
        const nameEl = el.querySelector('.friend-name');
        nameEl.style.cssText = 'font-weight: 600; font-size: 0.75rem; color: var(--color-text); white-space: nowrap;';
        return el;
    }

    function seatFriend(friendId) {
        const card = document.querySelector(`.friend-card[data-friend="${friendId}"]`);
        if (!card || card.classList.contains('seated')) return;

        card.classList.add('seated');
        card.style.pointerEvents = 'none';

        const seatedEl = createSeatedFriend(friendId);
        seatedFriends.appendChild(seatedEl);

        seatedCountValue++;
        updateCounts();

        // Haptic feedback if available
        if (navigator.vibrate) navigator.vibrate(50);
    }

    function unseatAll() {
        document.querySelectorAll('.friend-card.seated').forEach(card => {
            card.classList.remove('seated');
            card.style.pointerEvents = '';
        });
        seatedFriends.innerHTML = '';
        seatedCountValue = 0;
        updateCounts();
    }

    // Drag and drop
    friendCards.forEach(card => {
        card.addEventListener('dragstart', (e) => {
            if (card.classList.contains('seated')) {
                e.preventDefault();
                return;
            }
            card.classList.add('dragging');
            e.dataTransfer.setData('text/plain', card.dataset.friend);
            e.dataTransfer.effectAllowed = 'move';
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
        });

        // Click/tap to seat (mobile-friendly)
        card.addEventListener('click', () => {
            seatFriend(card.dataset.friend);
        });

        // Keyboard support
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                seatFriend(card.dataset.friend);
            }
        });
    });

    // Table drop zone
    tableArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        tableArea.style.borderColor = 'var(--color-primary)';
        tableArea.style.backgroundColor = 'var(--color-primary-bg)';
    });

    tableArea.addEventListener('dragleave', () => {
        tableArea.style.borderColor = '';
        tableArea.style.backgroundColor = '';
    });

    tableArea.addEventListener('drop', (e) => {
        e.preventDefault();
        tableArea.style.borderColor = '';
        tableArea.style.backgroundColor = '';
        const friendId = e.dataTransfer.getData('text/plain');
        if (friendId) seatFriend(friendId);
    });

    // Call everyone button
    callEveryoneBtn.addEventListener('click', () => {
        if (seatedCountValue === totalFriends) {
            unseatAll();
            callEveryoneBtn.innerHTML = `
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                <span>Call everyone</span>
            `;
        } else {
            friendCards.forEach(card => {
                if (!card.classList.contains('seated')) {
                    seatFriend(card.dataset.friend);
                }
            });
            callEveryoneBtn.innerHTML = `
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 17H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2z"></path><path d="M12 12l-4 4m0 0l4 4m-4-4h16"></path></svg>
                <span>Clear table</span>
            `;
        }
    });
});
</script>
@endsection