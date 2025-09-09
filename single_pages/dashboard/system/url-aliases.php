<?php

declare(strict_types=1);

use Concrete\Package\UrlAliases\RequestResolver;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Package\UrlAliases\Controller\SinglePage\Dashboard\System\UrlAliases $controller
 * @var Concrete\Core\Page\View\PageView $view
 * @var Concrete\Core\Validation\CSRF\Token $token
 * @var string $currentLocale
 * @var string $rootUrl
 * @var array $acceptLanguageDictionaries
 * @var string $currentAcceptLanguageHeader
 * @var array $urlAliases
 */

ob_start();
?>
<div class="row">
    <div class="col-sm-4">
        <div class="form-group">
            <label v-bind:for="`${idPrefix}-language`" class="form-label"><?= t('Language') ?></label>
            <div class="input-group input-group-sm">
                <select class="form-control" v-bind:id="`${idPrefix}-language`" v-bind:value="language" v-on:change="$emit('update-language', $event.target.value)" v-bind:disabled="disabled">
                    <option v-if="language === ''" value="">** <?= t('Please Select') ?> **</option>
                    <option v-for="l in DICTIONARY.LANGUAGES" v-bind:key="l.code" v-bind:value="l.code">{{ l.name }}</option>
                </select>
                <span class="input-group-addon input-group-text" v-if="language !== ''"><code>{{ language }}</code></span>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            <label v-bind:for="`${idPrefix}-script`" class="form-label"><?= t('Script') ?></label>
            <div class="input-group input-group-sm">
                <select class="form-control" v-bind:id="`${idPrefix}-script`" v-bind:value="script" v-on:input="$emit('update-script', $event.target.value)" v-bind:disabled="disabled">
                    <option v-if="allowAny" value="*">** <?= tc('Script', 'Any') ?> **</option>
                    <option value="">** <?= tc('Script', 'None') ?> **</option>
                    <option v-for="s in DICTIONARY.SCRIPTS" v-bind:key="s.code" v-bind:value="s.code">{{ s.name }}</option>
                </select>
                <span class="input-group-addon input-group-text" v-if="script !== '*' && script !== ''"><code>{{ script }}</code></span>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            <label v-bind:for="`${idPrefix}-territory`" class="form-label"><?= t('Territory') ?></label>
            <div class="input-group input-group-sm">
                <select class="form-control" v-bind:id="`${idPrefix}-territory`" v-bind:value="territory" v-on:input="$emit('update-territory', $event.target.value)" v-bind:disabled="disabled">
                    <option v-if="allowAny" value="*">** <?= tc('Territory', 'Any') ?> **</option>
                    <option value="">** <?= tc('Territory', 'None') ?> **</option>
                    <optgroup v-for="c in DICTIONARY.CONTINENTS" v-bind:key="c.name" v-bind:label="c.name">
                        <option v-for="t in c.territories" v-bind:key="t.code" v-bind:value="t.code">{{ t.name }}</option>
                    </optgroup>
                </select>
                <span class="input-group-addon input-group-text" v-if="territory !== '*' && territory !== ''"><code>{{ territory }}</code></span>
            </div>
        </div>
    </div>
</div>
<?php
$acceptHeaderBuilderTemplate = ob_get_contents();
ob_end_clean();
?>
<div id="ua-app" v-cloak>
    <div v-if="urlAliases.length === 0" class="alert alert-info">
        <?= t('No alias has been defined yet.') ?>
    </div>
    <div v-else>
        <table class="table table-sm table-condensed table-striped table-hover">
            <colgroup>
                <col width="1" />
                <col />
                <col />
                <col />
                <col />
                <col />
                <col />
                <col width="1" />
            </colgroup>
            <thead>
                <tr>
                    <th class="text-center">
                        <ua-list-sort-header
                            text=""
                            v-bind:selected="sort.key === 'enabled'"
                            v-bind:descending="sort.descending"
                            v-on:click="toggleSort('enabled')"
                        ></ua-list-sort-header>
                    </th>
                    <th>
                        <ua-list-sort-header
                            text="<?= t('Created') ?>"
                            v-bind:selected="sort.key === 'createdOn'"
                            v-bind:descending="sort.descending"
                            v-on:click="toggleSort('createdOn')"
                        ></ua-list-sort-header>
                    </th>
                    <th>
                        <ua-list-sort-header
                            text="<?= t('Alias') ?>"
                            v-bind:selected="sort.key === 'pathAndQuerystring'"
                            v-bind:descending="sort.descending"
                            v-on:click="toggleSort('pathAndQuerystring')"
                        ></ua-list-sort-header>
                    </th>
                    <th>
                        <ua-list-sort-header
                            text="<?= t('Target') ?>"
                            v-bind:selected="sort.key === 'targetInfo'"
                            v-bind:descending="sort.descending"
                            v-on:click="toggleSort('targetInfo')"
                        ></ua-list-sort-header>
                    </th>
                    <th>
                        <ua-list-sort-header
                            text="<?= t('First hit') ?>"
                            v-bind:selected="sort.key === 'firstHit'"
                            v-bind:descending="sort.descending"
                            v-on:click="toggleSort('firstHit')"
                        ></ua-list-sort-header>
                    </th>
                    <th>
                        <ua-list-sort-header
                            text="<?= t('Last hit') ?>"
                            v-bind:selected="sort.key === 'lastHit'"
                            v-bind:descending="sort.descending"
                            v-on:click="toggleSort('lastHit')"
                        ></ua-list-sort-header>
                    </th>
                    <th>
                        <ua-list-sort-header
                            text="<?= t('Hit count') ?>"
                            v-bind:selected="sort.key === 'hitCount'"
                            v-bind:descending="sort.descending"
                            v-on:click="toggleSort('hitCount')"
                        ></ua-list-sort-header>
                    </th>
                    <th>
                        <?= t('Actions') ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="ua in sortedUrlAliases" v-bind:key="ua.id" v-bind:class="{'table-warning': !ua.enabled, warning: !ua.enabled}">
                    <td class="text-center">
                        <a href="#" v-on:click.prevent="toggleEnabled(ua)" style="text-decoration: none">
                            <span v-if="ua._togglingEnabled" v-bind:title="<?= h('ua.enabled ? ' . json_encode(t('Disabling alias...')) . ' : ' . json_encode(t('Enabling alias...'))) ?>">
                                &#x25EF; <!-- LARGE CIRCLE -->
                            </span>
                            <span v-else-if="ua.enabled" title="<?= t('Enabled alias') ?>">
                                &#x1F7E2; <!-- LARGE GREEN CIRCLE -->
                            </span>
                            <span v-else title="<?= t('Disabled alias') ?>">
                                &#x1F534; <!-- LARGE RED CIRCLE -->
                            </span>
                        </a>
                    </td>
                    <td>{{ formatDateTime(ua.createdOn) }}</td>
                    <td><code>/{{ ua.pathAndQuerystring }}</code>
                    <td>
                        <div v-if="ua.targetInfo.error" class="alert alert-danger" style="margin: 0; padding: 0.3em; white-space: pre-wrap">{{ ua.targetInfo.error }}</div>
                        <a v-else target="_blank" v-bind:href="ua.targetInfo.url">{{ ua.targetInfo.displayName }}</a>
                        <ul class="list-unstyled mb-0">
                            <?= t('Targets by browser language') ?>
                            <li v-for="lt in ua.localizedTargets" v-bind:key="lt.it">
                                <a href="#" class="btn btn-sm btn-info" v-on:click.prevent="editLocalizedTarget(ua, lt)">
                                    <code>{{ describeLocalizedTarget(lt) }}</code>
                                </a>
                                <span v-if="lt.targetInfo.error" class="text-danger" style="white-space: pre-wrap">{{ lt.targetInfo.error }}</span>
                                <a v-else target="_blank" v-bind:href="lt.targetInfo.url">{{ lt.targetInfo.displayName }}</a>
                            </li>
                            <li>
                                <a href="#" class="btn btn-sm btn-info" v-on:click.prevent="editLocalizedTarget(ua)">Add</a>
                            </li>
                        </ul>
                    </td>
                    <td>{{ formatDateTime(ua.firstHit) }}</td>
                    <td>{{ formatDateTime(ua.lastHit) }}</td>
                    <td>{{ formatInteger(ua.hitCount) }}</td>
                    <td style="white-space: nowrap">
                        <a class="btn btn-sm btn-success" v-bind:class="{disabled: !ua.enabled}" v-bind:href="rootUrl + ua.pathAndQuerystring" v-on:click.prevent="testUrlAlias(ua)"><?= t('Test') ?></a>
                        <button class="btn btn-sm btn-danger" v-on:click.prevent="deleteUrlAlias(ua)"><?= t('Delete') ?></button>
                        <button class="btn btn-sm btn-default btn-secondary" v-on:click.prevent="editUrlAlias(ua, true)"><?= t('Clone') ?></button>
                        <button class="btn btn-sm btn-primary" v-on:click.prevent="editUrlAlias(ua)"><?= t('Edit') ?></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div style="display: none">
        <div id="ua-urlalias-test" class="ccm-ui">
            <div class="form-group">
                <label for="ua-urlalias-test-urlsuffix" class="form-label"><?= t('URL to be tested') ?></label>
                <div class="input-group">
                    <span class="input-group-addon input-group-text">{{ rootUrl }}</span>
                    <input type="text" class="form-control" v-model.trim="testing.urlSuffix" spellcheck="false" ref="testUrlSuffix" />
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('Test with specific browser language') ?></label>
                <div v-if="currentAcceptLanguageHeader !== ''" class="form-check">
                    <input class="form-check-input" type="radio" value="current" v-model="testing.useAcceptLanguageHeader" name="ua-urlalias-test-use-ac" id="ua-urlalias-test-use-ac-current">
                    <label class="form-check-label" for="ua-urlalias-test-use-ac-current">
                        <?= t('Use your browser default configuration') ?>
                        <span class="small text-muted">(<code class="text-muted">{{ currentAcceptLanguageHeader }}</code>)</span>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" value="empty" v-model="testing.useAcceptLanguageHeader" name="ua-urlalias-test-use-ac" id="ua-urlalias-test-use-ac-empty">
                    <label class="form-check-label" for="ua-urlalias-test-use-ac-empty">
                        <?= t('Use an empty browser language') ?>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" value="custom" v-model="testing.useAcceptLanguageHeader" name="ua-urlalias-test-use-ac" id="ua-urlalias-test-use-ac-custom">
                    <label class="form-check-label" for="ua-urlalias-test-use-ac-custom">
                        <?= t('Use an custom browser language') ?>
                    </label>
                    <ua-accept-header-builder
                        v-bind:disabled="testing.useAcceptLanguageHeader !== 'custom'"
                        v-bind:allow-any="false"
                        v-bind:language="testing.customAcceptLanguageHeader.language" v-on:update-language="testing.customAcceptLanguageHeader.language = $event"
                        v-bind:script="testing.customAcceptLanguageHeader.script" v-on:update-script="testing.customAcceptLanguageHeader.script = $event"
                        v-bind:territory="testing.customAcceptLanguageHeader.territory" v-on:update-territory="testing.customAcceptLanguageHeader.territory = $event"
                    >
                    </ua-accept-header-builder>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= t('Result') ?></label>
                <iframe style="width: 100%; height: 150px; border: 0; inset: 0" v-bind:style="{visibility: testing.displayResult ? 'visible' : 'hidden'}" name="ua-urlalias-test-iframe" ref="testIFrame"></iframe>
            </div>
            <div class="dialog-buttons">
                <button class="btn btn-secondary pull-left" v-on:click.prevent="hideTestDialog()"><?= t('Close') ?></button>
                <button class="btn btn-primary pull-right" v-on:click.prevent="startTestUrl()"><?= t('Test') ?></button>
            </div>
        </div>
    </div>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <div class="float-start pull-left">
                <button class="btn" v-bind:class="{'btn-success': autorefreshEnabled, 'btn-secondary': !autorefreshEnabled}" v-on:click.prevent="autorefreshEnabled = !autorefreshEnabled" style="padding-left: 10px; padding-right: 10px">
                    <span style="visibility: hidden" class="ua-loading-icon"></span>
                    <?= t('Auto-refresh') ?>
                    <span v-bind:style="{visibility: autorefreshing ? 'visible' : 'hidden'}" class="ua-loading-icon"></span>
                </button>
            </div>
            <div class="float-end pull-right">
                <button class="btn btn-primary" v-on:click.prevent="editUrlAlias()"><?= t('Add') ?></button>
            </div>
        </div>
    </div>
</div>
<script>
(function() {

const DATETIME_FORMATTER = new Intl.DateTimeFormat(<?= json_encode(str_replace('_', '-', $currentLocale)) ?>, {
    dateStyle: 'medium',
    timeStyle: 'medium',
});

const INTEGER_FORMATTER = new Intl.NumberFormat(<?= json_encode(str_replace('_', '-', $currentLocale)) ?>, {
    maximumFractionDigits: 0,
});

const COLLATOR = new Intl.Collator(<?= json_encode(str_replace('_', '-', $currentLocale)) ?>, {
    sensitivity: 'base',
});
const AUTOREFRESH_INTERVAL = 5000;
let autorefreshTimer = null;

let urlAliasApp;

const eventHooks = (function() {
    async function invoke(method, e, successCallback) {
        let success = false;
        try {
            await urlAliasApp[method](e.detail.data, successCallback);
            if (successCallback) {
                return;
            }
            success = true;
        } catch (x) {
            ConcreteAlert.error({message: x?.message || x || <?= json_encode(t('Unknown error')) ?>});
        }
        if (!successCallback) {
            e.detail.done(success);
        }
    };

    return {
        saveUrlAlias: async (e) => {
            invoke('saveUrlAlias', e);
        },
        saveLocalizedTarget: async (e) => {
            invoke('saveLocalizedTarget', e);
        },
        deleteLocalizedTarget: async (e) => {
            invoke('deleteLocalizedTarget', e, () => {
                e.detail.done();
            });
        },
    };
})();

function ready() {

    Vue.component('ua-list-sort-header', {
        props: {
            text: {
                type: String,
                required: true,
            },
            selected: {
                type: Boolean,
                required: true,
            },
            descending: {
                type: Boolean,
                required: true,
            },
        },
        template: `<a href="#" v-on:click.prevent="$emit('click')" style="display: inline-block; min-width: 1em; text-align: center; color: inherit; text-decoration: none">
            {{ text }}
            <span v-if="selected !== true" style="opacity: 0.3">
                &#x21C5; <!-- UPWARDS ARROW LEFTWARDS OF DOWNWARDS ARROW -->
            </span>
            <span v-else-if="descending">
                &#x2193; <!-- DOWNWARDS ARROW -->
            </span>
            <span v-else>
                &#x2191; <!-- UPWARDS ARROW -->
            </span>
        </a>`,
    });

    let uaAcceptHeaderBuilderCounter = 0;

    Vue.component('ua-accept-header-builder', {
        props: {
            language: {
                type: String,
                required: true,
            },
            script: {
                type: String,
                required: true,
            },
            territory: {
                type: String,
                required: true,
            },
            allowAny: {
                type: Boolean,
                required: true,
            },
            disabled: {
                type: Boolean,
                default: false,
            },
        },
        data() {
            return {
                idPrefix: '',
                DICTIONARY: <?= json_encode($acceptLanguageDictionaries) ?>,
            };
        },
        beforeMount() {
            this.idPrefix = `ua-accept-header-builder-${++uaAcceptHeaderBuilderCounter}`;
        },
        template: <?= json_encode($acceptHeaderBuilderTemplate) ?>,
    });

    urlAliasApp = new Vue({
        el: '#ua-app',
        data() {
            return {
                SORT_OPTIONS: {
                    enabled: {
                        defaultDescending: true,
                    },
                    createdOn: {
                        defaultDescending: true,
                    },
                    targetInfo: {
                        sorter: (urlAliasA, urlAliasB) => {
                            if (urlAliasA.targetInfo.error) {
                                return urlAliasB.targetInfo.error ? COLLATOR.compare(urlAliasA.targetInfo.error, urlAliasB.targetInfo.error) : -1;
                            }
                            if (urlAliasB.targetInfo.error) {
                                return 1;
                            }
                            return COLLATOR.compare(urlAliasA.targetInfo.displayName, urlAliasB.targetInfo.displayName) || (urlAliasB.id - urlAliasA.id);
                        },
                    },
                    firstHit: {
                        defaultDescending: true,
                    },
                    lastHit: {
                        defaultDescending: true,
                    },
                    hitCount: {
                        defaultDescending: true,
                    },
                },
                sort: {
                    key: '',
                    options: null,
                    sorter: () => 0,
                    descending: false,
                },
                autorefreshEnabled: false,
                autorefreshing: false,
                rootUrl: <?= json_encode($rootUrl) ?>,
                currentAcceptLanguageHeader: <?= json_encode($currentAcceptLanguageHeader) ?>,
                testing: {
                    urlSuffix: '',
                    useAcceptLanguageHeader: <?= json_encode($currentAcceptLanguageHeader === '' ? 'empty' : 'current') ?>,
                    customAcceptLanguageHeader: {
                        language: '',
                        script: '',
                        territory: '',
                    },
                    displayResult: false,
                },
                urlAliases: <?= json_encode($urlAliases) ?>.map((urlAlias) => this.unserializeUrlAlias(urlAlias)),
            };
        },
        mounted() {
            window.addEventListener('ccm.url_aliases.saveUrlAlias', eventHooks.saveUrlAlias);
            window.addEventListener('ccm.url_aliases.saveLocalizedTarget', eventHooks.saveLocalizedTarget);
            window.addEventListener('ccm.url_aliases.deleteLocalizedTarget', eventHooks.deleteLocalizedTarget);
            this.toggleSort('createdOn');
            if (this.autorefreshEnabled) {
                clearTimeout(autorefreshTimer);
                autorefreshTimer = setTimeout(() => this.autorefresh(), AUTOREFRESH_INTERVAL);
            }
        },
        destroyed() {
            window.removeEventListener('ccm.url_aliases.deleteLocalizedTarget', eventHooks.deleteLocalizedTarget);
            window.removeEventListener('ccm.url_aliases.saveLocalizedTarget', eventHooks.saveLocalizedTarget);
            window.removeEventListener('ccm.url_aliases.saveUrlAlias', eventHooks.saveUrlAlias);
        },
        watch: {
            autorefreshEnabled() {
                if (this.autorefreshEnabled) {
                    clearTimeout(autorefreshTimer);
                    autorefreshTimer = null;
                    this.autorefresh();
                } else {
                    clearTimeout(autorefreshTimer);
                    autorefreshTimer = null;
                }
            },
        },
        methods: {
            unserializeUrlAlias(urlAlias) {
                ['createdOn', 'firstHit', 'lastHit'].forEach((field) => {
                    if (typeof urlAlias[field] === 'number') {
                        const d = new Date();
                        d.setTime(urlAlias[field] * 1000);
                        urlAlias[field] = d;
                    }
                });
                if (!urlAlias._togglingEnabled) {
                    urlAlias._togglingEnabled = false;
                }
                return urlAlias;
            },
            formatDateTime(value) {
                return value ? DATETIME_FORMATTER.format(value) : '';
            },
            formatInteger(value) {
                return typeof value === 'number' ? INTEGER_FORMATTER.format(value) : '';
            },
            describeLocalizedTarget(localizedTarget) {
                return [
                    localizedTarget.language,
                    localizedTarget.script,
                    localizedTarget.territory,
                ].filter(c => c !== '').join('-');
            },
            toggleSort(key) {
                if (this.sort.key === key) {
                    this.sort.descending = !this.sort.descending;
                } else {
                    this.sort.key = key;
                    this.sort.options = this.SORT_OPTIONS[key] || null;
                    this.sort.descending = this.sort.options?.defaultDescending || false;
                    this.sort.sorter = this.sort.options?.sorter;
                    if (!this.sort.sorter) {
                        this.sort.sorter = (urlAliasA, urlAliasB) => {
                            if (urlAliasA[this.sort.key] < urlAliasB[this.sort.key]) {
                                return -1;
                            }
                            if (urlAliasA[this.sort.key] > urlAliasB[this.sort.key]) {
                                return 1;
                            }
                            return urlAliasB.id - urlAliasA.id;
                        };
                    }
                }
            },
            async toggleEnabled(urlAlias) {
                if (urlAlias._togglingEnabled) {
                    return;
                }
                urlAlias._togglingEnabled = true;
                try {
                    const response = await this.ajax(
                        <?= json_encode($view->action('setUrlAliasEnabled')) ?>,
                        <?= json_encode($token->generate('ua-urlalias-setenabled')) ?>,
                        {
                            id: urlAlias.id,
                            enable: !urlAlias.enabled,
                        }
                    );
                    urlAlias.enabled = response.enabled;
                } catch (x) {
                    ConcreteAlert.error({message: x?.message || x || <?= json_encode(t('Unknown error')) ?>});
                } finally {
                    urlAlias._togglingEnabled = false;
                }
            },
            async autorefresh() {
                if (this.autorefreshing) {
                    return;
                }
                this.autorefreshing = true;
                if (autorefreshTimer) {
                    clearTimeout(autorefreshTimer);
                    autorefreshTimer = null;
                }
                try {
                    const urlAliases = await this.ajax(
                        <?= json_encode($view->action('autoRefresh')) ?>,
                        <?= json_encode($token->generate('ua-autorefresh')) ?>
                    );
                    urlAliases.forEach((urlAlias) => this.addOrRefresh(this.unserializeUrlAlias(urlAlias)));
                } catch (x) {
                    console.log(x);
                } finally {
                    clearTimeout(autorefreshTimer);
                    autorefreshTimer = this.autorefreshEnabled ? setTimeout(() => this.autorefresh(), AUTOREFRESH_INTERVAL) : null;
                    this.autorefreshing = false;
                }
            },
            editUrlAlias(urlAlias, asNew) {
                jQuery.fn.dialog.open({
                    width: Math.min(Math.max(window.innerWidth - 50, 300), 800),
                    height: 'auto',
                    modal: true,
                    title: urlAlias ? (asNew ? <?= json_encode(t('Clone Alias')) ?> : <?= json_encode(t('Edit Alias')) ?>) : <?= json_encode(t('Add Alias')) ?>,
                    href: <?= json_encode((string) $view->action('edit-url-alias')) ?> + `?id=${urlAlias?.id || 'new'}&asNew=${asNew ? 1 : 0}`,
                });
            },
            async saveUrlAlias(data) {
                const urlAlias = await this.ajax(
                    <?= json_encode($view->action('saveUrlAlias')) ?>,
                    <?= json_encode($token->generate('ua-urlalias-save')) ?>,
                    data
                );
                this.addOrRefresh(this.unserializeUrlAlias(urlAlias));
            },
            async deleteUrlAlias(urlAlias, confirmed) {
                if (!confirmed) {
                    ConcreteAlert.confirm(
                        <?= json_encode(t('Are you sure you want to delete this alias?')) ?>,
                        () => {
                            jQuery.fn.dialog.closeTop();
                            this.deleteUrlAlias(urlAlias, true);
                        },
                        'btn-danger',
                        <?= json_encode(t('Delete')) ?>
                    );
                    return;
                }
                jQuery.fn.dialog.showLoader();
                try {
                    await this.ajax(
                        <?= json_encode($view->action('deleteUrlAlias')) ?>,
                        <?= json_encode($token->generate('ua-urlalias-delete')) ?>,
                        {
                            id: urlAlias.id,
                        }
                    );
                } catch (x) {
                    ConcreteAlert.error({message: x?.message || x || <?= json_encode(t('Unknown error')) ?>});
                    return;
                } finally {
                    jQuery.fn.dialog.hideLoader();
                }
                const index = this.urlAliases.indexOf(urlAlias);
                if (index >= 0) {
                    this.urlAliases.splice(index, 1);
                }
            },
            editLocalizedTarget(urlAlias, localizedTarget) {
                jQuery.fn.dialog.open({
                    width: Math.min(Math.max(window.innerWidth - 50, 300), 800),
                    height: 'auto',
                    modal: true,
                    title: localizedTarget ? <?= json_encode(t('Edit Target by browser language')) ?> : <?= json_encode(t('Add Target by browser language')) ?>,
                    href: <?= json_encode((string) $view->action('edit-localized-target')) ?> + `?urlAlias=${urlAlias.id}&id=${localizedTarget?.id || 'new'}`,
                });
            },
            async saveLocalizedTarget(data) {
                const urlAlias = await this.ajax(
                    <?= json_encode($view->action('saveLocalizedTarget')) ?>,
                    <?= json_encode($token->generate('ua-localizedtarget-save')) ?>,
                    data
                );
                this.addOrRefresh(this.unserializeUrlAlias(urlAlias));
            },
            async deleteLocalizedTarget(data, successCallback, confirmed) {
                if (!confirmed) {
                    ConcreteAlert.confirm(
                        <?= json_encode(t('Are you sure you want to delete this Target by browser language?')) ?>,
                        () => {
                            jQuery.fn.dialog.closeTop();
                            this.deleteLocalizedTarget(data, successCallback, true);
                        },
                        'btn-danger',
                        <?= json_encode(t('Delete')) ?>
                    );
                    return;
                }
                jQuery.fn.dialog.showLoader();
                let urlAlias;
                try {
                    urlAlias = await this.ajax(
                        <?= json_encode($view->action('deleteLocalizedTarget')) ?>,
                        <?= json_encode($token->generate('ua-localizedtarget-delete')) ?>,
                        data
                    );
                } catch (x) {
                    ConcreteAlert.error({message: x?.message || x || <?= json_encode(t('Unknown error')) ?>});
                    return;
                } finally {
                    jQuery.fn.dialog.hideLoader();
                }
                this.addOrRefresh(this.unserializeUrlAlias(urlAlias));
                successCallback();
            },
            addOrRefresh(urlAlias) {
                const existing = this.urlAliases.find((ua) => ua.id === urlAlias.id);
                if (existing) {
                    Object.keys(urlAlias).forEach((k) => {
                        if (k[0] !== '_') {
                            existing[k] = urlAlias[k]
                        }
                    });
                } else {
                    this.urlAliases.push(urlAlias);
                }
            },

            testUrlAlias(urlAlias) {
                if (!urlAlias?.id || !urlAlias.enabled) {
                    return;
                }
                this.testing.urlSuffix = urlAlias.pathAndQuerystring;
                jQuery.fn.dialog.open({
                    element: '#ua-urlalias-test',
                    modal: true,
                    width: Math.min(Math.max(window.innerWidth - 50, 500), 900),
                    title: <?= json_encode(t('Test URL Alias')) ?>,
                    height: 'auto',
                    open: () => {
                        this.$refs.testUrlSuffix.focus();
                    },
                    close: () => {
                        this.testing.displayResult = false;
                    },
                });
                return;
            },
            startTestUrl() {
                try {
                    this.testing.displayResult = false;
                    this.$refs.testIFrame.src = 'about:blank';
                    try {
                        this.$refs.testIFrame.contentWindow.document.open();
                        this.$refs.testIFrame.contentWindow.document.write('');
                        this.$refs.testIFrame.contentWindow.document.close();
                    } catch {
                    }
                    let urlSuffix = this.testing.urlSuffix.replace(/^\/+/, '');
                    if (urlSuffix === '') {
                        this.$refs.testUrlSuffix.focus();
                        throw new Error(<?= json_encode(t('Please specify the URL to be tested')) ?>);
                    }
                    const testUrl = this.rootUrl + urlSuffix;
                    try {
                        new window.URL(testUrl);
                    } catch {
                        this.$refs.testUrlSuffix.focus();
                        throw new Error(<?= json_encode(t('The URL to be tested is not valid')) ?>);
                    }
                    const form = document.createElement('form');
                    form.style.display = 'none';
                    form.action = testUrl;
                    form.method = 'POST';
                    form.target = this.$refs.testIFrame.name;
                    const addInput = (name, value) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = value;
                        form.appendChild(input);
                    };
                    addInput(<?= json_encode(RequestResolver::TESTFIELD_TOKEN) ?>, <?= json_encode($token->generate(RequestResolver::TESTFIELD_TOKEN)) ?>);
                    switch (this.testing.useAcceptLanguageHeader) {
                        case 'current':
                            break;
                        case 'empty':
                            addInput(<?= json_encode(RequestResolver::TESTFIELD_OVERRIDEACCEPTLANGUAGE) ?>, '');
                            break;
                        case 'custom':
                            if (this.testing.customAcceptLanguageHeader.language === '') {
                                throw new Error(<?= json_encode(t('Please specify the custom language')) ?>);
                            }
                            addInput(
                                <?= json_encode(RequestResolver::TESTFIELD_OVERRIDEACCEPTLANGUAGE) ?>,
                                [
                                    this.testing.customAcceptLanguageHeader.language,
                                    this.testing.customAcceptLanguageHeader.script,
                                    this.testing.customAcceptLanguageHeader.territory,
                                ].filter((c) => c !== '').join('-')
                            );
                            break;
                    }
                    document.body.appendChild(form);
                    form.submit();
                    form.remove();
                    setTimeout(() => this.testing.displayResult = true, 100);
                } catch (x) {
                    ConcreteAlert.error({message: x?.message || x || <?= json_encode(t('Unknown error')) ?>});
                }
            },
            hideTestDialog() {
                jQuery.fn.dialog.closeTop();
            },
            async ajax(url, token, bodyParams) {
                const request = {
                method: 'POST',
                    headers: {
                        Accept: 'application/json',
                    },
                    body: new FormData(),
                };
                request.body.append('__ccm_consider_request_as_xhr', '1');
                request.body.append(<?= json_encode($token::DEFAULT_TOKEN_NAME) ?>, token);
                if (bodyParams) {
                    Object.keys(bodyParams).forEach((k) => request.body.append(k, bodyParams[k]));
                }
                const response = await window.fetch(
                    url,
                    request
                );
                const responseText = await response.text();
                let responseData;
                try {
                    responseData = JSON.parse(responseText);
                } catch {
                    throw new Error(responseText);
                }
                if (responseData?.error) {
                    if (typeof responseData.error === 'string') {
                        throw new Error(responseData.error);
                    }
                    if (responseData.errors instanceof Array && typeof responseData.errors[0] === 'string') {
                        throw new Error(responseData.errors[0]);
                    }
                    throw new Error(responseText);
                }
                return responseData;
            },
        },
        computed: {
            sortedUrlAliases() {
                const result = [].concat(this.urlAliases);
                if (this.sort.key) {
                    result.sort(this.sort.sorter);
                    if (this.sort.descending) {
                        result.reverse();
                    }
                }
                return result;
            },
        },
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ready);
} else {
    ready();
}

})();
</script>
