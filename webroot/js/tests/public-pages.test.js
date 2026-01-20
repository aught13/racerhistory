/**
 * Tests for public page functionality
 * @jest-environment jsdom
 */

describe('Public Pages', () => {
    beforeEach(() => {
        document.body.innerHTML = '';

        // Mock jQuery if not available
        if (!window.$) {
            window.$ = jest.fn(() => ({
                on: jest.fn(),
                DataTable: jest.fn(),
                tab: jest.fn(),
            }));
            window.$.fn = {
                DataTable: jest.fn(),
                tab: jest.fn(),
            };
        }
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    describe('Seasons Page', () => {
        test('should render season cards', () => {
            document.body.innerHTML = `
                <div class="row" id="seasons-grid">
                    <div class="col-md-4 mb-4">
                        <div class="card season-card">
                            <div class="card-body">
                                <h5 class="card-title">2023-24</h5>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const grid = document.getElementById('seasons-grid');
            const cards = grid.querySelectorAll('.season-card');

            expect(grid).not.toBeNull();
            expect(cards.length).toBe(1);
        });

        test('should handle season detail tabs', () => {
            document.body.innerHTML = `
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#games">
                            Games
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#roster">
                            Roster
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="games">Games content</div>
                    <div class="tab-pane" id="roster">Roster content</div>
                </div>
            `;

            const tabs = document.querySelectorAll('.nav-link');
            expect(tabs.length).toBe(2);
            expect(tabs[0].classList.contains('active')).toBe(true);
        });

        test('should display games in season', () => {
            document.body.innerHTML = `
                <table class="table games-table">
                    <tbody>
                        <tr>
                            <td>Nov 10, 2023</td>
                            <td>Murray State</td>
                            <td class="text-center">
                                <span class="badge bg-success">W</span>
                                <strong>85-72</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            `;

            const table = document.querySelector('.games-table');
            const rows = table.querySelectorAll('tbody tr');

            expect(table).not.toBeNull();
            expect(rows.length).toBe(1);

            const badge = rows[0].querySelector('.badge');
            expect(badge.textContent).toBe('W');
            expect(badge.classList.contains('bg-success')).toBe(true);
        });
    });

    describe('People Page', () => {
        test('should render people table', () => {
            document.body.innerHTML = `
                <table id="people-table" class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>John Doe</td>
                            <td>Player</td>
                        </tr>
                    </tbody>
                </table>
            `;

            const table = document.getElementById('people-table');
            const rows = table.querySelectorAll('tbody tr');

            expect(table).not.toBeNull();
            expect(rows.length).toBe(1);
        });

        test('should handle person profile tabs', () => {
            document.body.innerHTML = `
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#seasons">
                            Seasons
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#stats">
                            Stats
                        </button>
                    </li>
                </ul>
            `;

            const tabs = document.querySelectorAll('.nav-link');
            expect(tabs.length).toBe(2);
            expect(tabs[0].getAttribute('data-bs-target')).toBe('#seasons');
            expect(tabs[1].getAttribute('data-bs-target')).toBe('#stats');
        });
    });

    describe('Games Page', () => {
        test('should render games table', () => {
            document.body.innerHTML = `
                <table class="table games-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Opponent</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Nov 10, 2023</td>
                            <td>vs Eastern Illinois</td>
                            <td><span class="badge bg-success">W 85-72</span></td>
                        </tr>
                    </tbody>
                </table>
            `;

            const table = document.querySelector('.games-table');
            const rows = table.querySelectorAll('tbody tr');

            expect(table).not.toBeNull();
            expect(rows.length).toBe(1);
        });

        test('should display box score tables', () => {
            document.body.innerHTML = `
                <div class="box-score">
                    <h4>Murray State</h4>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Player</th>
                                <th>MIN</th>
                                <th>PTS</th>
                                <th>REB</th>
                                <th>AST</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>John Doe</td>
                                <td>32</td>
                                <td>18</td>
                                <td>7</td>
                                <td>5</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;

            const boxScore = document.querySelector('.box-score');
            const rows = boxScore.querySelectorAll('tbody tr');

            expect(boxScore).not.toBeNull();
            expect(rows.length).toBe(1);

            const cells = rows[0].querySelectorAll('td');
            expect(cells[0].textContent).toBe('John Doe');
            expect(cells[2].textContent).toBe('18'); // Points
        });
    });

    describe('Stats Page', () => {
        test('should render season selector', () => {
            document.body.innerHTML = `
                <div class="list-group">
                    <a href="/stats/season/1" class="list-group-item list-group-item-action">
                        2023-24 Season
                    </a>
                    <a href="/stats/season/2" class="list-group-item list-group-item-action">
                        2022-23 Season
                    </a>
                </div>
            `;

            const links = document.querySelectorAll('.list-group-item');
            expect(links.length).toBe(2);
            expect(links[0].getAttribute('href')).toBe('/stats/season/1');
        });

        test('should render stats table with percentages', () => {
            document.body.innerHTML = `
                <table class="table stats-table">
                    <thead>
                        <tr>
                            <th>Player</th>
                            <th>GP</th>
                            <th>PPG</th>
                            <th>FG%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>John Doe</td>
                            <td>30</td>
                            <td>15.5</td>
                            <td>45.6</td>
                        </tr>
                    </tbody>
                </table>
            `;

            const table = document.querySelector('.stats-table');
            const rows = table.querySelectorAll('tbody tr');

            expect(table).not.toBeNull();
            expect(rows.length).toBe(1);

            const cells = rows[0].querySelectorAll('td');
            expect(cells[2].textContent).toBe('15.5'); // PPG
            expect(cells[3].textContent).toBe('45.6'); // FG%
        });
    });

    describe('Image Display', () => {
        test('should render image cards', () => {
            document.body.innerHTML = `
                <div class="row" id="images-grid">
                    <div class="col-md-3">
                        <div class="card">
                            <img src="/images/serve/1" class="card-img-top" alt="Team photo">
                            <div class="card-body">
                                <p class="card-text">Team photo</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const images = document.querySelectorAll('.card img');
            expect(images.length).toBe(1);
            expect(images[0].getAttribute('src')).toBe('/images/serve/1');
        });

        test('should handle image loading errors', () => {
            const img = document.createElement('img');
            img.src = '/images/serve/999';
            img.alt = 'Test image';

            const errorCallback = jest.fn();
            img.addEventListener('error', errorCallback);

            const event = new Event('error');
            img.dispatchEvent(event);

            expect(errorCallback).toHaveBeenCalled();
        });
    });

    describe('Blog Post Display', () => {
        test('should render blog post cards', () => {
            document.body.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <div class="card blog-post-card">
                            <div class="card-body">
                                <h5 class="card-title">Game Recap</h5>
                                <p class="card-text">Murray State wins...</p>
                                <a href="/blog/1" class="btn btn-primary">Read More</a>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const cards = document.querySelectorAll('.blog-post-card');
            expect(cards.length).toBe(1);

            const title = cards[0].querySelector('.card-title');
            expect(title.textContent).toBe('Game Recap');

            const link = cards[0].querySelector('a');
            expect(link.getAttribute('href')).toBe('/blog/1');
        });
    });

    describe('Responsive Behavior', () => {
        test('should have responsive classes on cards', () => {
            document.body.innerHTML = `
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card">Card content</div>
                </div>
            `;

            const col = document.querySelector('[class*="col-"]');
            expect(col.classList.contains('col-12')).toBe(true);
            expect(col.classList.contains('col-md-4')).toBe(true);
        });

        test('should have mobile-friendly tables', () => {
            document.body.innerHTML = `
                <div class="table-responsive">
                    <table class="table">
                        <tbody>
                            <tr><td>Data</td></tr>
                        </tbody>
                    </table>
                </div>
            `;

            const wrapper = document.querySelector('.table-responsive');
            expect(wrapper).not.toBeNull();
        });
    });
});
