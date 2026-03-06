<?php open('nav') ?>
    <a class="nav-brand" href="/">Sauerkraut</a>
    <div class="nav-links">
        <a class="nav-link active" href="/">Home</a>
    </div>
    <div class="nav-actions">
        <?= component('theme-toggle') ?>
        <?= component('button', ['variant' => 'primary'], 'Get Started') ?>
    </div>
<?php close() ?>

<main style="max-width: 900px; margin: 0 auto; padding: var(--space-xl);">

    <header style="margin-bottom: var(--space-xl);">
        <h1 style="font-size: var(--text-3xl); font-weight: 700;">Components</h1>
        <p style="color: var(--text-1); margin-top: var(--space-sm);">
            Pure CSS components with HSL theming. Toggle dark mode with your OS settings.
        </p>
    </header>

    <section style="display: grid; gap: var(--space-lg);">

        <h2 style="font-size: var(--text-xl); font-weight: 600;">Cards</h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--space-lg);">

            <?php open('card') ?>
                <div class="card-body">
                    <h3 class="card-title">Container Queries</h3>
                    <p class="card-description">Cards respond to their own width, not the viewport. Resize to see layout changes.</p>
                    <div class="card-footer">
                        <?= component('button', ['variant' => 'primary'], 'Learn More') ?>
                        <?= component('button', ['variant' => 'ghost'], 'Source') ?>
                    </div>
                </div>
            <?php close() ?>

            <?php open('card') ?>
                <div class="card-body">
                    <h3 class="card-title">HSL Theming</h3>
                    <p class="card-description">All colors use HSL with light-dark() for automatic theme switching. Rotate the hue for a new palette.</p>
                    <div class="card-footer">
                        <?= component('button', ['variant' => 'primary'], 'Explore') ?>
                    </div>
                </div>
            <?php close() ?>

            <?php open('card') ?>
                <div class="card-body">
                    <h3 class="card-title">Cascade Layers</h3>
                    <p class="card-description">@layer controls specificity so component styles never fight with your theme or reset. No !important needed.</p>
                </div>
            <?php close() ?>

        </div>

        <h2 style="font-size: var(--text-xl); font-weight: 600; margin-top: var(--space-lg);">Table</h2>

        <?php
        $users = [
            (object) ['id' => 1, 'name' => 'Alice Park', 'role' => 'Engineer'],
            (object) ['id' => 2, 'name' => 'Bob Chen', 'role' => 'Designer'],
            (object) ['id' => 3, 'name' => 'Carol Singh', 'role' => 'PM'],
            (object) ['id' => 4, 'name' => 'Dan Novak', 'role' => 'Engineer'],
        ];
        ?>

        <?php open('table', ['id' => 'users']) ?>
            <?php open('table/head') ?>
                <th>Name</th>
                <th>Role</th>
                <th>Status</th>
                <th>Updated</th>
                <th>Actions</th>
            <?php close() ?>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <?= \Sauerkraut\View\View::partial('partials/htmx-row', ['id' => $u->id, 'name' => $u->name, 'role' => $u->role]) ?>
                <?php endforeach; ?>
            </tbody>
        <?php close() ?>

        <h2 style="font-size: var(--text-xl); font-weight: 600; margin-top: var(--space-lg);">Alerts</h2>

        <div id="htmx-alert-target"></div>
        <button class="btn btn-primary" hx-get="/htmx/alert" hx-target="#htmx-alert-target" hx-swap="innerHTML">Load Alert via htmx</button>

        <h2 style="font-size: var(--text-xl); font-weight: 600; margin-top: var(--space-lg);">Buttons</h2>

        <div style="display: flex; flex-wrap: wrap; gap: var(--space-sm);">
            <?= component('button', [], 'Default') ?>
            <?= component('button', ['variant' => 'primary'], 'Primary') ?>
            <?= component('button', ['variant' => 'danger'], 'Danger') ?>
            <?= component('button', ['variant' => 'ghost'], 'Ghost') ?>
            <?= component('button/icon', ['icon' => '★'], 'Starred') ?>
            <?= component('button/icon', ['icon' => '✕', 'variant' => 'danger'], 'Delete') ?>
        </div>

    </section>
</main>
