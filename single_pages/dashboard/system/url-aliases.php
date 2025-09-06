<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Package\UrlAliases\Controller\SinglePage\Dashboard\System\UrlAliases $controller
 * @var Concrete\Core\Page\View\PageView $view
 * @var Concrete\Core\Validation\CSRF\Token $token
 * @var string $currentLocale
 * @var string $rootUrl
 * @var array $urlAliases
 */

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
let editEventHook = null;

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

    new Vue({
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
                urlAliases: <?= json_encode($urlAliases) ?>.map((urlAlias) => this.unserializeUrlAlias(urlAlias)),
            };
        },
        mounted() {
            this.toggleSort('createdOn');
            editEventHook = async (e) => {
                let success = false;
                try {
                    await this.saveUrlAlias(e.detail.data);
                    success = true;
                } catch (x) {
                    ConcreteAlert.error({message: x?.message || x || <?= json_encode(t('Unknown error')) ?>});
                }
                e.detail.done(success);
            };
            window.addEventListener('ccm.url_aliases.saveUrlAlias', editEventHook);
            if (this.autorefreshEnabled) {
                clearTimeout(autorefreshTimer);
                autorefreshTimer = setTimeout(() => this.autorefresh(), AUTOREFRESH_INTERVAL);
            }
        },
        destroyed() {
            if (editEventHook) {
                window.removeEventListener('ccm.url_aliases.saveUrlAlias', editEventHook);
                editEventHook = null;
            }
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
                    height: 500,
                    modal: true,
                    title: urlAlias ? (asNew ? <?= json_encode(t('Clone Alias')) ?> : <?= json_encode(t('Edit Alias')) ?>) : <?= json_encode(t('Add Alias')) ?>,
                    href: <?= json_encode((string) $view->action('edit')) ?> + `?id=${urlAlias?.id || 'new'}&asNew=${asNew ? 1 : 0}`,
                });
            },
            async saveUrlAlias(data) {
                const urlAlias = await this.ajax(
                    <?= json_encode($view->action('saveUrlAlias')) ?>,
                    <?= json_encode($token->generate('ua-urlalias-save')) ?>,
                    data
                );
                this.addOrRefresh(urlAlias);
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
                let question = <?= json_encode(t('Please specify the URL to be checked')) ?>;
                let testUrl = this.rootUrl + urlAlias.pathAndQuerystring;
                while (true) {
                    testUrl = window.prompt(question, testUrl)?.replace(/^\s+|\s+/g, '');
                    if (!testUrl) {
                        return;
                    }
                    try {
                        new URL(testUrl);
                    } catch {
                        question = <?= json_encode(t('The URL is not valid. Retry')) ?>;
                        continue;
                    }
                    break;
                }
                const dialog = document.createElement('dialog');
                dialog.style.padding = '0';
                dialog.style.border = 'none';
                dialog.style.width = '75vw';
                dialog.style.height = '300px';
                dialog.style.maxHeight = '90vh';
                dialog.style.margin = 'auto';
                dialog.style.position = 'relative';
                dialog.addEventListener('close', () => dialog.remove());
                dialog.addEventListener('click', (e) => {
                    if (e.target === dialog) {
                        dialog.close();
                    }
                });
                const iframeContainer = document.createElement('div');
                const iframe = document.createElement('iframe');
                iframe.name = 'dialog_iframe_' + Date.now();
                iframe.style.width = '100%';
                iframe.style.height = '100%';
                iframe.style.border = '0';
                iframe.style.inset = '0';
                iframe.style.position = 'absolute';
                dialog.appendChild(iframe);
                const form = document.createElement('form');
                form.action = testUrl;
                form.method = 'POST';
                form.target = iframe.name;
                form.style.display = 'none';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ua-testing_url_aliases_token';
                input.value = <?= json_encode($token->generate('ua-testing_url_aliases_token')) ?>;
                form.appendChild(input);
                document.body.appendChild(dialog);
                document.body.appendChild(form);
                form.submit();
                form.remove();
                dialog.showModal();
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