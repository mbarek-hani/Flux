<x-app-layout>
    <x-slot name="title">
        Tableau de bord
    </x-slot>

    <div class="p-page">
        <div class="p-container p-container--lg">
            <h2 class="p-page__title">Tableau de bord</h2>

            @if($noSite)
                <x-card>
                    <div class="p-empty">
                        <x-custom-icon name="globe" class="p-empty__icon" />
                        <h3 class="p-empty__title">Aucun site configuré</h3>
                        <p class="p-empty__text">Ajoutez votre premier site pour commencer.</p>
                        <div class="p-mt-3">
                            <x-button variant="primary" href="{{ route('sites.create') }}">
                                <x-custom-icon name="plus" class="c-icon--sm" />
                                Ajouter un site
                            </x-button>
                        </div>
                    </div>
                </x-card>
            @else

                {{-- Alpine : état global du dashboard partagé avec les plugins --}}
                <div x-data="dashboard()" x-init="chargerStats()" id="dashboard-root">
                    {{-- Contrôles --}}
                    <div class="p-dash__controls">
                        <select x-model="siteId" @change="chargerStats()" class="p-dash__select">
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" {{ $site->id === $currentSite->id ? 'selected' : '' }}>
                                    {{ $site->name }} — {{ $site->domain }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Période Desktop --}}
                        <div class="p-dash__periods">
                            <template x-for="p in periodes" :key="p.value">
                                <button @click="period = p.value; chargerStats()" :class="period === p.value
                                            ? 'p-dash__period-btn--active'
                                            : ''" class="p-dash__period-btn" x-text="p.label"></button>
                            </template>
                        </div>

                        {{-- Période Mobile --}}
                        <select x-model="period" @change="chargerStats()" class="p-dash__period-mobile">
                            <template x-for="p in periodes" :key="p.value">
                                <option :value="p.value" x-text="p.label"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Onglets --}}
                    <div class="p-dash__tabs">
                        <nav class="p-dash__tab-nav">

                            <button @click="ongletActif = 'apercu'" :class="ongletActif === 'apercu'
                                        ? 'p-dash__tab--active'
                                        : ''" class="p-dash__tab">
                                <x-custom-icon name="chart-bar" class="c-icon--sm" />
                                Vue d'ensemble
                            </button>

                            <button @click="ongletActif = 'evenements'" :class="ongletActif === 'evenements'
                                        ? 'p-dash__tab--active'
                                        : ''" class="p-dash__tab">
                                <x-custom-icon name="cursor-click" class="c-icon--sm" />
                                Événements
                            </button>

                            {{-- Onglets plugins --}}
                            @foreach($tabs as $tab)
                                <button @click="ongletActif = '{{ $tab['id'] }}'" :class="ongletActif === '{{ $tab['id'] }}'
                                                    ? 'p-dash__tab--active'
                                                    : ''" class="p-dash__tab">
                                    <x-custom-icon :name="$tab['icon']" class="c-icon--sm" />
                                    {{ $tab['label'] }}
                                </button>
                            @endforeach
                        </nav>
                    </div>

                    {{-- Chargement --}}
                    <div x-show="chargement" x-cloak class="p-dash__loading">
                        <x-custom-icon name="arrow-path" class="p-dash__loading-icon" />
                        <span class="p-dash__loading-text">Chargement...</span>
                    </div>

                    {{-- Erreur --}}
                    <div x-show="erreur" x-cloak class="p-dash__error">
                        <p class="p-dash__error-text" x-text="erreur"></p>
                    </div>

                    <div x-show="!chargement && !erreur" x-cloak>

                        {{-- ══════ VUE D'ENSEMBLE ══════ --}}
                        <div x-show="ongletActif === 'apercu'" x-cloak>
                            <div class="p-grid p-grid--2 p-grid--4 p-mb-3">
                                <x-stats-card title="Visiteurs uniques" icon="users">
                                    <x-slot:value>
                                        <span x-text="stats?.summary?.unique_visitors ?? 0"></span>
                                    </x-slot:value>
                                </x-stats-card>
                                <x-stats-card title="Pages vues" icon="eye">
                                    <x-slot:value>
                                        <span x-text="stats?.summary?.page_views ?? 0"></span>
                                    </x-slot:value>
                                </x-stats-card>
                                <x-stats-card title="Taux de rebond" icon="arrow-trending-down">
                                    <x-slot:value>
                                        <span x-text="(stats?.summary?.bounce_rate ?? 0) + '%'"></span>
                                    </x-slot:value>
                                </x-stats-card>
                                <x-stats-card title="Durée moyenne" icon="clock">
                                    <x-slot:value>
                                        <span x-text="formaterDuree(stats?.summary?.average_duration ?? 0)"></span>
                                    </x-slot:value>
                                </x-stats-card>
                            </div>

                            <x-card title="Évolution du trafic" class="p-mb-3">
                                <div class="p-dash__chart">
                                    <canvas x-ref="graphique"></canvas>
                                </div>
                            </x-card>

                            <div class="p-dash__grid-2">

                                <x-card title="Pages populaires" :padding="false">
                                    <table class="p-dash__table">
                                        <thead>
                                            <tr>
                                                <th>Page</th>
                                                <th class="p-dash__table-th--right">Vues</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="page in (stats?.top_pages ?? []).slice(0, 8)"
                                                :key="page.path">
                                                <tr>
                                                    <td class="p-dash__table-cell--truncate" x-text="page.path"></td>
                                                    <td class="p-dash__table-cell--right" x-text="page.views"></td>
                                                </tr>
                                            </template>
                                            <template x-if="!stats?.top_pages?.length">
                                                <tr>
                                                    <td colspan="2" class="p-dash__table-cell--center-lg">Aucune donnée</td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </x-card>

                                <x-card title="Sources de trafic" :padding="false">
                                    <table class="p-dash__table">
                                        <thead>
                                            <tr>
                                                <th>Source</th>
                                                <th class="p-dash__table-th--right">Visiteurs</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="ref in (stats?.top_referrers ?? []).slice(0, 8)"
                                                :key="ref.referrer_domain">
                                                <tr>
                                                    <td>
                                                        <div class="p-dash__list-name">
                                                            <img :src="'https://www.google.com/s2/favicons?domain=' + ref.referrer_domain + '&sz=16'"
                                                                class="p-dash__favicon">
                                                            <span class="p-dash__table-cell--truncate"
                                                                x-text="ref.referrer_domain"></span>
                                                        </div>
                                                    </td>
                                                    <td class="p-dash__table-cell--right" x-text="ref.visitors"></td>
                                                </tr>
                                            </template>
                                            <template x-if="!stats?.top_referrers?.length">
                                                <tr>
                                                    <td colspan="2" class="p-dash__table-cell--center-lg">Aucun référent
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </x-card>

                                <x-card title="Pays">
                                    <div x-ref="cartePays" style="width: 100%; height: 250px;"></div>
                                </x-card>

                                <x-card title="Appareils">
                                    <div class="p-dash__devices">
                                        <div class="p-dash__devices-chart">
                                            <canvas x-ref="graphiqueAppareils"></canvas>
                                        </div>
                                        <div class="p-dash__devices-legend">
                                            <template x-for="app in stats?.devices ?? []" :key="app.device">
                                                <div class="p-dash__device-row">
                                                    <span class="p-dash__device-name" x-text="app.device"></span>
                                                    <span class="p-dash__device-count" x-text="app.visitors"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </x-card>
                            </div>

                            <div class="p-dash__grid-2 p-mt-3">
                                <x-card title="Systèmes & Navigateurs" :padding="false">
                                    <div class="p-grid p-grid--2" style="gap: 0;">
                                        <div style="border-right: 1px solid var(--gray-200);">
                                            <table class="p-dash__table">
                                                <thead>
                                                    <tr>
                                                        <th>Navigateur</th>
                                                        <th class="p-dash__table-th--right">Visiteurs</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="nav in (stats?.top_browsers ?? []).slice(0, 5)"
                                                        :key="nav.browser">
                                                        <tr>
                                                            <td>
                                                                <div class="p-dash__list-name">
                                                                    <template x-if="getBrowserIcon(nav.browser)">
                                                                        <img :src="getBrowserIcon(nav.browser)"
                                                                            class="p-dash__favicon">
                                                                    </template>
                                                                    <span class="p-dash__table-cell--truncate"
                                                                        x-text="nav.browser"></span>
                                                                </div>
                                                            </td>
                                                            <td class="p-dash__table-cell--right" x-text="nav.visitors">
                                                            </td>
                                                        </tr>
                                                    </template>
                                                    <template x-if="!stats?.top_browsers?.length">
                                                        <tr>
                                                            <td colspan="2" class="p-dash__table-cell--center-lg">Aucune
                                                                donnée</td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div>
                                            <table class="p-dash__table">
                                                <thead>
                                                    <tr>
                                                        <th>Système d'exploitation</th>
                                                        <th class="p-dash__table-th--right">Visiteurs</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="sys in (stats?.top_operating_systems ?? []).slice(0, 5)"
                                                        :key="sys.operating_system">
                                                        <tr>
                                                            <td>
                                                                <div class="p-dash__list-name">
                                                                    <template x-if="getOsIcon(sys.operating_system)">
                                                                        <img :src="getOsIcon(sys.operating_system)"
                                                                            class="p-dash__favicon">
                                                                    </template>
                                                                    <span class="p-dash__table-cell--truncate"
                                                                        x-text="sys.operating_system"></span>
                                                                </div>
                                                            </td>
                                                            <td class="p-dash__table-cell--right" x-text="sys.visitors">
                                                            </td>
                                                        </tr>
                                                    </template>
                                                    <template x-if="!stats?.top_operating_systems?.length">
                                                        <tr>
                                                            <td colspan="2" class="p-dash__table-cell--center-lg">Aucune
                                                                donnée</td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </x-card>

                                <x-card title="Campagnes Marketing (UTM)" :padding="false">
                                    <div x-data="{ ongletUtm: 'source' }">
                                        <div class="p-dash__tabs p-px-3 p-pt-2"
                                            style="border-bottom: 1px solid var(--gray-200); margin-bottom: 0;">
                                            <nav class="p-dash__tab-nav">
                                                <button @click="ongletUtm = 'source'"
                                                    :class="ongletUtm === 'source' ? 'p-dash__tab--active' : ''"
                                                    class="p-dash__tab"
                                                    style="padding: 0.375rem 0.5rem; font-size: 0.75rem;">Source</button>
                                                <button @click="ongletUtm = 'medium'"
                                                    :class="ongletUtm === 'medium' ? 'p-dash__tab--active' : ''"
                                                    class="p-dash__tab"
                                                    style="padding: 0.375rem 0.5rem; font-size: 0.75rem;">Medium</button>
                                                <button @click="ongletUtm = 'campagne'"
                                                    :class="ongletUtm === 'campagne' ? 'p-dash__tab--active' : ''"
                                                    class="p-dash__tab"
                                                    style="padding: 0.375rem 0.5rem; font-size: 0.75rem;">Campagne</button>
                                            </nav>
                                        </div>
                                        <table class="p-dash__table" x-show="ongletUtm === 'source'">
                                            <thead>
                                                <tr>
                                                    <th>Source</th>
                                                    <th class="p-dash__table-th--right">Visiteurs</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="utm in (stats?.top_utm_sources ?? []).slice(0, 5)"
                                                    :key="utm.utm_source">
                                                    <tr>
                                                        <td>
                                                            <div class="p-dash__list-name">
                                                                <template x-if="getUtmIcon(utm.utm_source)">
                                                                    <img :src="getUtmIcon(utm.utm_source)"
                                                                        class="p-dash__favicon">
                                                                </template>
                                                                <span class="p-dash__table-cell--truncate"
                                                                    x-text="utm.utm_source"></span>
                                                            </div>
                                                        </td>
                                                        <td class="p-dash__table-cell--right" x-text="utm.visitors"></td>
                                                    </tr>
                                                </template>
                                                <template x-if="!stats?.top_utm_sources?.length">
                                                    <tr>
                                                        <td colspan="2" class="p-dash__table-cell--center-lg">Aucune donnée
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                        <table class="p-dash__table" x-show="ongletUtm === 'medium'" x-cloak>
                                            <thead>
                                                <tr>
                                                    <th>Medium</th>
                                                    <th class="p-dash__table-th--right">Visiteurs</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="utm in (stats?.top_utm_mediums ?? []).slice(0, 5)"
                                                    :key="utm.utm_medium">
                                                    <tr>
                                                        <td class="p-dash__table-cell--truncate" x-text="utm.utm_medium">
                                                        </td>
                                                        <td class="p-dash__table-cell--right" x-text="utm.visitors"></td>
                                                    </tr>
                                                </template>
                                                <template x-if="!stats?.top_utm_mediums?.length">
                                                    <tr>
                                                        <td colspan="2" class="p-dash__table-cell--center-lg">Aucune donnée
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                        <table class="p-dash__table" x-show="ongletUtm === 'campagne'" x-cloak>
                                            <thead>
                                                <tr>
                                                    <th>Campagne</th>
                                                    <th class="p-dash__table-th--right">Visiteurs</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="utm in (stats?.top_utm_campaigns ?? []).slice(0, 5)"
                                                    :key="utm.utm_campaign">
                                                    <tr>
                                                        <td class="p-dash__table-cell--truncate" x-text="utm.utm_campaign">
                                                        </td>
                                                        <td class="p-dash__table-cell--right" x-text="utm.visitors"></td>
                                                    </tr>
                                                </template>
                                                <template x-if="!stats?.top_utm_campaigns?.length">
                                                    <tr>
                                                        <td colspan="2" class="p-dash__table-cell--center-lg">Aucune donnée
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </x-card>
                            </div>

                            @hook('dashboard.widgets')
                        </div>

                        {{-- ══════ ÉVÉNEMENTS ══════ --}}
                        <div x-show="ongletActif === 'evenements'" x-cloak>
                            {{-- Cartes de statistiques --}}
                            <div class="p-grid p-grid--2 p-grid--4 p-mb-3">
                                <x-stats-card title="Total événements" icon="cursor-click">
                                    <x-slot:value>
                                        <span x-text="totalEvenements()"></span>
                                    </x-slot:value>
                                </x-stats-card>
                                <x-stats-card title="Événements / Session" icon="arrows-right-left">
                                    <x-slot:value>
                                        <span x-text="moyenneEvtParSession()"></span>
                                    </x-slot:value>
                                </x-stats-card>
                                <x-stats-card title="Types différents" icon="tag">
                                    <x-slot:value>
                                        <span x-text="stats?.events_by_name?.length ?? 0"></span>
                                    </x-slot:value>
                                </x-stats-card>
                                <x-stats-card title="Sessions actives" icon="users">
                                    <x-slot:value>
                                        <span x-text="totalSessionsEvenements()"></span>
                                    </x-slot:value>
                                </x-stats-card>
                            </div>

                            {{-- Graphique d'activité Temporelle --}}
                            <x-card title="Activité des événements" class="p-mb-3">
                                <div class="p-dash__chart--tall">
                                    <canvas x-ref="graphiqueEvenements"></canvas>
                                </div>
                            </x-card>

                            <div class="p-dash__grid-2">
                                {{-- Répartition par nom + Graphique Doughnut --}}
                                <x-card title="Répartition des types">
                                    <div class="p-dash__distribution">
                                        <div class="p-dash__distribution-chart">
                                            <canvas x-ref="graphiqueEvtRepart"></canvas>
                                        </div>
                                        <div class="p-dash__distribution-table">
                                            <table class="p-dash__table">
                                                <tbody>
                                                    <template
                                                        x-for="(evt, index) in (stats?.events_by_name ?? []).slice(0, 5)"
                                                        :key="evt.name">
                                                        <tr class="p-dash__evt-row">
                                                            <td class="p-row">
                                                                <div class="p-dash__evt-dot"
                                                                    :style="'background-color: ' + couleursChart[index]">
                                                                </div>
                                                                <span class="p-dash__evt-name" x-text="evt.name"></span>
                                                            </td>
                                                            <td class="p-dash__table-cell--right" x-text="evt.total"></td>
                                                            <td class="p-dash__table-cell--muted"
                                                                x-text="Math.round((evt.total / totalEvenements()) * 100) + '%'">
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </x-card>

                                {{-- Top des pages où se produisent les événements --}}
                                <x-card title="Pages les plus interactives" :padding="false">
                                    <table class="p-dash__table">
                                        <thead>
                                            <tr class="p-dash__table-header--tinted">
                                                <th>URL</th>
                                                <th class="p-dash__table-th--right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="page in (stats?.events_by_page ?? []).slice(0, 6)"
                                                :key="page.path">
                                                <tr>
                                                    <td class="p-dash__table-cell--truncate" x-text="page.path"></td>
                                                    <td class="p-dash__table-cell--mono" x-text="page.total"></td>
                                                </tr>
                                            </template>
                                            <template x-if="!stats?.events_by_page?.length">
                                                <tr>
                                                    <td colspan="2" class="p-dash__table-cell--center-xl">Aucune donnée
                                                        contextuelle</td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </x-card>

                                {{-- Journal des derniers événements --}}
                                <x-card title="Dernière activité" :padding="false" class="p-dash__col-span-2">
                                    <table class="p-dash__table">
                                        <thead>
                                            <tr class="p-dash__table-header--tinted">
                                                <th>Événement</th>
                                                <th>Page</th>
                                                <th class="p-dash__table-th--right">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="evt in (stats?.latest_events ?? []).slice(0, 8)"
                                                :key="evt.id">
                                                <tr>
                                                    <td>
                                                        <span class="p-dash__evt-badge" x-text="evt.name"></span>
                                                    </td>
                                                    <td class="p-dash__table-cell--truncate" x-text="evt.path"
                                                        style="max-width: 250px; color: var(--gray-500);"></td>
                                                    <td class="p-dash__table-cell--muted"
                                                        x-text="formaterDate(evt.created_at)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </x-card>
                            </div>
                        </div>

                        {{-- ══════ ONGLETS PLUGINS ══════ --}}
                        @foreach($tabs as $tab)
                            <div x-show="ongletActif === '{{ $tab['id'] }}'" x-cloak>
                                @hook('tab.' . $tab['id'])
                            </div>
                        @endforeach

                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')

        <script>
            function dashboard() {
                return {
                    siteId: {{ $currentSite->id ?? 0 }},
                    period: '7d',
                    ongletActif: 'apercu',
                    chargement: true,
                    erreur: null,
                    stats: null,
                    _chart: null,
                    _chartApp: null,
                    _chartEvt: null,
                    _chartEvtRepart: null,
                    _map: null,
                    couleursChart: ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981'], // Indigo, Violet, Pink, Amber, Emerald

                    periodes: [
                        { value: 'today', label: "Aujourd'hui" },
                        { value: '7d', label: '7 jours' },
                        { value: '30d', label: '30 jours' },
                        { value: 'this_month', label: 'Ce mois' },
                        { value: 'this_year', label: 'Année' },
                    ],

                    async chargerStats() {
                        this.chargement = true;
                        this.erreur = null;
                        try {
                            const p = new URLSearchParams({ site_id: this.siteId, period: this.period });
                            const r = await fetch('/dashboard/stats?' + p, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                            });
                            if (!r.ok) throw new Error('Erreur ' + r.status);
                            this.stats = await r.json();
                            this.$nextTick(() => {
                                this.dessinerGraphique();
                                this.dessinerAppareils();
                                this.dessinerEvenements();
                                this.dessinerRepartitionEvenements();
                                this.dessinerCarte();
                            });
                        } catch (e) {
                            this.erreur = e.message;
                        } finally {
                            this.chargement = false;
                        }
                    },

                    dessinerGraphique() {
                        const c = this.$refs.graphique;
                        if (!c) return;
                        if (this._chart) this._chart.destroy();
                        const d = this.stats?.evolution ?? [];
                        this._chart = new Chart(c, {
                            type: 'line',
                            data: {
                                labels: d.map(e => new Date(e.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })),
                                datasets: [
                                    {
                                        label: 'Visiteurs',
                                        data: d.map(e => e.visitors),
                                        borderColor: '#f71',
                                        backgroundColor: 'rgba(55,65,81,0.05)',
                                        fill: true, tension: 0.3, borderWidth: 2, pointRadius: 2,
                                    },
                                    {
                                        label: 'Pages vues',
                                        data: d.map(e => e.page_views),
                                        borderColor: '#93f',
                                        backgroundColor: 'rgba(156,163,175,0.05)',
                                        fill: true, tension: 0.3, borderWidth: 2, pointRadius: 2,
                                    },
                                ],
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                interaction: { intersect: false, mode: 'index' },
                                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                                plugins: { legend: { position: 'top', labels: { usePointStyle: true, padding: 15, font: { size: 11 } } } },
                            },
                        });
                    },

                    dessinerAppareils() {
                        const c = this.$refs.graphiqueAppareils;
                        if (!c) return;
                        if (this._chartApp) this._chartApp.destroy();
                        const d = this.stats?.devices ?? [];
                        if (!d.length) return;
                        this._chartApp = new Chart(c, {
                            type: 'doughnut',
                            data: {
                                labels: d.map(a => a.device),
                                datasets: [{ data: d.map(a => a.visitors), backgroundColor: ['#fab', '#afb', '#abf', '#666'], borderWidth: 0 }],
                            },
                            options: { responsive: true, maintainAspectRatio: true, cutout: '60%', plugins: { legend: { display: false } } },
                        });
                    },

                    dessinerCarte() {
                        const c = this.$refs.cartePays;
                        if (!c) return;

                        const values = {};
                        for (const p of (this.stats?.top_countries ?? [])) {
                            const code = (p.country_code || '').toUpperCase();
                            if (code) {
                                values[code] = p.visitors;
                            }
                        }

                        if (this._map) {
                            this._map.destroy();
                        }

                        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

                        this._map = new jsVectorMap({
                            selector: c,
                            map: 'world',
                            backgroundColor: 'transparent',
                            zoomOnScroll: false,
                            regionStyle: {
                                initial: {
                                    fill: isDark ? '#374151' : '#e5e7eb',
                                    stroke: isDark ? '#1f2937' : '#ffffff',
                                    strokeWidth: 1,
                                    fillOpacity: 1
                                },
                                hover: {
                                    fill: isDark ? '#10b981' : '#34d399',
                                    cursor: 'pointer'
                                }
                            },
                            visualizeData: {
                                scale: isDark ? ['#065f46', '#34d399'] : ['#a7f3d0', '#047857'],
                                values: values
                            },
                            onRegionTooltipShow(event, tooltip, code) {
                                const count = values[code] || 0;
                                tooltip.text(
                                    `<div style="text-align: center; font-family: sans-serif;">
                                            <div style="font-weight: bold; font-size: 0.875rem;">${tooltip.text()}</div>
                                            <div style="font-size: 0.75rem; color: #d1d5db;">${count} visiteur(s)</div>
                                        </div>`,
                                    true
                                );
                            }
                        });
                    },

                    getBrowserIcon(name) {
                        if (!name) return '';
                        const n = name.toLowerCase();
                        if (n.includes('chrome')) return 'https://cdnjs.cloudflare.com/ajax/libs/browser-logos/70.4.0/chrome/chrome_16x16.png';
                        if (n.includes('firefox')) return 'https://cdnjs.cloudflare.com/ajax/libs/browser-logos/70.4.0/firefox/firefox_16x16.png';
                        if (n.includes('safari')) return 'https://cdnjs.cloudflare.com/ajax/libs/browser-logos/70.4.0/safari/safari_16x16.png';
                        if (n.includes('edge')) return 'https://cdnjs.cloudflare.com/ajax/libs/browser-logos/70.4.0/edge/edge_16x16.png';
                        if (n.includes('opera')) return 'https://cdnjs.cloudflare.com/ajax/libs/browser-logos/70.4.0/opera/opera_16x16.png';
                        if (n.includes('brave')) return 'https://cdnjs.cloudflare.com/ajax/libs/browser-logos/70.4.0/brave/brave_16x16.png';
                        return '';
                    },

                    getOsIcon(name) {
                        if (!name) return '';
                        const n = name.toLowerCase();
                        if (n.includes('windows')) return 'https://img.icons8.com/color/16/windows-10.png';
                        if (n.includes('mac') || n.includes('ios')) return 'https://img.icons8.com/color/16/mac-os.png';
                        if (n.includes('linux') || n.includes('ubuntu')) return 'https://img.icons8.com/color/16/linux.png';
                        if (n.includes('android')) return 'https://img.icons8.com/color/16/android-os.png';
                        return '';
                    },

                    getUtmIcon(name) {
                        if (!name) return '';
                        const n = name.toLowerCase();
                        let domain = n;
                        if (['google', 'facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'tiktok', 'pinterest', 'reddit'].includes(n)) {
                            domain = n + '.com';
                        }
                        if (domain.includes('.')) {
                            return 'https://www.google.com/s2/favicons?domain=' + domain + '&sz=16';
                        }
                        return '';
                    },

                    formaterDuree(s) {
                        if (!s || s < 1) return '0s';
                        const m = Math.floor(s / 60), sec = Math.floor(s % 60);
                        return m > 0 ? m + 'm ' + sec + 's' : sec + 's';
                    },

                    formaterDate(d) {
                        if (!d) return '';
                        return new Date(d).toLocaleDateString('fr-FR', {
                            day: 'numeric', month: 'short',
                            hour: '2-digit', minute: '2-digit',
                        });
                    },

                    drapeau(code) {
                        if (!code || code.length !== 2) return '';
                        return String.fromCodePoint(
                            ...code.toUpperCase().split('').map(c => c.charCodeAt(0) + 127397)
                        );
                    },
                    dessinerEvenements() {
                        const c = this.$refs.graphiqueEvenements;
                        if (!c) return;
                        if (this._chartEvt) this._chartEvt.destroy();
                        const d = this.stats?.events_by_day ?? [];

                        this._chartEvt = new Chart(c, {
                            type: 'line', // Changé de 'bar' à 'line' pour mieux voir la tendance
                            data: {
                                labels: d.map(e => new Date(e.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })),
                                datasets: [{
                                    label: 'Événements',
                                    data: d.map(e => e.total),
                                    borderColor: '#6366f1',
                                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                    fill: true,
                                    tension: 0.4,
                                    borderWidth: 3,
                                    pointRadius: 3
                                }],
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { beginAtZero: true, grid: { display: false } },
                                    x: { grid: { display: false } }
                                }
                            },
                        });
                    },

                    // Nouveau graphique de répartition
                    dessinerRepartitionEvenements() {
                        const c = this.$refs.graphiqueEvtRepart;
                        if (!c) return;
                        if (this._chartEvtRepart) this._chartEvtRepart.destroy();

                        const data = this.stats?.events_by_name ?? [];
                        if (!data.length) return;

                        this._chartEvtRepart = new Chart(c, {
                            type: 'doughnut',
                            data: {
                                labels: data.map(e => e.name),
                                datasets: [{
                                    data: data.map(e => e.total),
                                    backgroundColor: this.couleursChart,
                                    borderWidth: 2,
                                    borderColor: '#ffffff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                cutout: '75%',
                                plugins: { legend: { display: false } }
                            }
                        });
                    },

                    totalEvenements() {
                        return (this.stats?.events_by_name ?? []).reduce((s, e) => s + e.total, 0);
                    },

                    totalSessionsEvenements() {
                        return (this.stats?.events_by_name ?? []).reduce((s, e) => s + (e.sessions || 0), 0);
                    },

                    // Nouvelle métrique
                    moyenneEvtParSession() {
                        const total = this.totalEvenements();
                        const sessions = this.totalSessionsEvenements();
                        if (!sessions) return 0;
                        return (total / sessions).toFixed(1);
                    },
                }
            }
        </script>
    @endpush
</x-app-layout>