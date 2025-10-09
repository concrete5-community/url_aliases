<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Validation\CSRF\Token $token
 * @var Concrete\Core\Page\View\PageView $view
 * @var Concrete\Package\UrlAliases\Controller\SinglePage\Dashboard\System\UrlAliases\BrokenLinks $controller
 * @var Concrete\Core\Form\Service\Form $form
 * @var bool $logEnabled
 * @var string[] $availableMethods
 * @var Concrete\Core\Localization\Localization $localization
 *
 * If $logEnabled is false:
 * @var string $optionsUrl
 */

if (!$logEnabled) {
    ?>
    <div class="alert alert-warning alert-dismissible">
        <button type="button" class="btn-close close" data-bs-dismiss="alert" data-dismiss="alert"><?= version_compare(APP_VERSION, '9') < 0 ? '&times;' : '' ?></button>
        <?= t('Logging is currently disabled: you can enable it in the %sOptions Page%s.', '<a href="' . h($optionsUrl) . '">', '</a>') ?>
    </div>
    <?php
}
?>

<div class="ccm-dashboard-header-buttons">
    <div id="ua-app-filter" v-cloak>
        <div v-if="availableMethods.length !== 0" class="input-group input-group-sm" <?= version_compare(APP_VERSION, '9') < 0 ? ' style="max-width: 50%; float: right"' : '' ?>>
            <span class="input-group-addon input-group-text"><?= t('Filter') ?></span>
            <div style="display: flex">
                <select class="form-control form-control" v-model="method" style="width: 40%" v-on:change="apply()" v-bind:disabled="disabled">
                    <option v-bind:value="null"><?= t('Method') ?></option>
                    <option v-for="m in availableMethods" v-bind:value="m">{{ m }}</option>
                </select>
                <input class="form-control" v-model.trim="path" type="search" placeholder="<?= t('Search path') ?>" style="width: 60%" v-on:keyup.enter.prevent="apply()" v-bind:readonly="disabled" />
            </div>
            <span class="input-group-btn">
                <button class="btn btn-secondary btn-default" v-on:click.prevent="apply()" v-bind:disabled="disabled">
                    &#x1F50D; <!-- LEFT-POINTING MAGNIFYING GLASS -->
                </button>
            </span>
            <span class="input-group-btn">
                <button class="btn btn-danger" v-on:click.prevent="askDeleteAll()" v-bind:disabled="disabled">
                    <?= t('Delete all') ?>
                </button>
            </span>
        </div>
    </div>
</div>

<div id="ua-app" v-cloak>
    <div v-if="availableMethods.length === 0">
        <div class="alert alert-info">
            <?= t('No log entry found') ?>
        </div>
     </div>
    <div v-else-if="loadError !== null" class="alert alert-danger">
        <div style="white-space: pre-wrap">{{ loadError }}</div>
    </div>
    <div v-else-if="entries === null">
        <?= t('Loading... ') ?>
    </div>
    <div v-else-if="entries.length === 0" class="alert alert-info">
        <?= t('No entry has been found') ?>
    </div>
    <div v-else>
        <table class="table table-condensed table-sm table-striped">
            <colgroup>
                <col width="1" />
                <col width="1" />
            </colgroup>
            <thead>
                <tr>
                    <td></td>
                    <th>
                        <ua-list-sort-header
                            text="<?= t('Method') ?>"
                            v-bind:selected="sort.by === 'method'"
                            v-bind:descending="sort.descending"
                            v-on:click="toggleSort('method')"
                        ></ua-list-sort-header>
                    </th>
                    <th>
                        <ua-list-sort-header
                            text="<?= t('Path') ?>"
                            v-bind:selected="sort.by === 'fullPath'"
                            v-bind:descending="sort.descending"
                            v-on:click="toggleSort('fullPath')"
                        ></ua-list-sort-header>
                    </th>
                    <th>
                        <ua-list-sort-header
                            text="<?= t('First hit') ?>"
                            v-bind:selected="sort.by === 'firstHit'"
                            v-bind:descending="sort.descending"
                            v-on:click="toggleSort('firstHit')"
                        ></ua-list-sort-header>
                    </th>
                    <th>
                        <ua-list-sort-header
                            text="<?= t('Last hit') ?>"
                            v-bind:selected="sort.by === 'lastHit'"
                            v-bind:descending="sort.descending"
                            v-on:click="toggleSort('lastHit')"
                        ></ua-list-sort-header>
                    </th>
                    <th>
                        <ua-list-sort-header
                            text="<?= t('Hit count') ?>"
                            v-bind:selected="sort.by === 'hitCount'"
                            v-bind:descending="sort.descending"
                            v-on:click="toggleSort('hitCount')"
                        ></ua-list-sort-header>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="entry in entries" v-bind:key="entry.id">
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-xs btn-danger" v-on:click.prevent="askDeleteEntry(entry)" v-bind:disabled="busy">
                            &#x274C; <!-- CROSS MARK -->
                        </button>
                    </td>
                    <td>
                        <code>{{ entry.method }}</code>
                    </td>
                    <td>
                        <code>{{ entry.fullPath }}</code>
                    </td>
                    <td class="text-nowrap">
                        {{ formatDateTime(entry.firstHit) }}
                    </td>
                    <td class="text-nowrap">
                        {{ formatDateTime(entry.lastHit) }}
                    </td>
                    <td class="text-end text-right">
                        {{ formatNumber(entry.hitCount) }}
                    </td>
                </tr>
            </tbody>
        </table>
        <div v-if="hasMoreEntries" class="text-center" style="margin-top: 10px">
            <button class="btn btn-secondary btn-default" v-on:click.prevent="fetchNextPage()" v-bind:disabled="busy">
                <?= t('Load more') ?>
            </button>
        </div>
    </div>
</div>


<script>(function() {

const DATETIME_FORMAT = new Intl.DateTimeFormat(
    <?= json_encode(str_replace('_', '-', $localization->getLocale())) ?>,
    {
        dateStyle: 'medium',
        timeStyle: 'medium',
    }
);

const NUMBER_FORMAT = new Intl.NumberFormat(
    <?= json_encode(str_replace('_', '-', $localization->getLocale())) ?>,
    {
        style: 'decimal',
    }
);

async function fetchJson(url, token, data)
{
    const request = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Accept: 'application/json'
        },
        body: new URLSearchParams(data || {}),
    };
    request.body.append(<?= json_encode($token::DEFAULT_TOKEN_NAME) ?>, token);
    request.body.append('__ccm_consider_request_as_xhr', '1');
    const response = await window.fetch(url, request);
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
    if (!response.ok) {
        throw new Error(responseText);
    }

    return responseData;
}

let filterApp = null;

function createFilter(mainApp, availableMethods)
{
    filterApp = new Vue({
        el: '#ua-app-filter',
        data() {
            return {
                availableMethods,
                disabled: false,
                method: null,
                path: '',
            };
        },
        methods: {
            apply() {
                if (this.disabled) {
                    return;
                }
                const filter = {};
                if (this.method !== null) {
                    filter.method = this.method;
                }
                if (this.path !== '') {
                    filter.path = this.path;
                }
                mainApp.applyFilter(filter);
            },
            askDeleteAll() {
                if (this.disabled) {
                    return;
                }
                ConcreteAlert.confirm(
                    <?= json_encode(t('Are you sure you want to delete all logged entries?')) ?>,
                    () => {
                        if (this.disabled) {
                            return;
                        }
                        mainApp.deleteAll((success) => {
                            if (success) {
                                jQuery.fn.dialog.closeTop();
                            }
                        });
                    },
                    'btn-danger',
                    <?= json_encode(t('Delete')) ?>
                );
            },
        },
    });
}

function areObjectsEqual(a, b) {
    if (!a) {
        return b ? false : true;
    }
    if (!b) {
        return false;
    }
    const aKeys = Object.keys(a);
    const bKeys = Object.keys(b);
    if (aKeys.length !== bKeys.length) {
        return false;
    }
    return aKeys.every(key => bKeys.includes(key) && a[key] === b[key]);
}

function ready()
{
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
        template: `<a href="#" v-on:click.prevent="$emit('click')" style="white-space: nowrap; display: inline-block; min-width: 1em; text-align: center; color: inherit; text-decoration: none">
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
                busy: false,
                sort: {
                    by: '',
                    options: null,
                    descending: false,
                },
                loadError: null,
                filter: {},
                entries: null,
                hasMoreEntries: false,
                availableMethods: <?= json_encode($availableMethods) ?>,
            };
        },
        mounted() {
            createFilter(this, this.availableMethods);
            this.toggleSort('lastHit');
        },
        watch: {
            busy() {
                if (filterApp) {
                    filterApp.disabled = this.busy;
                }
            },
        },
        methods: {
            applyFilter(filter) {
                if (this.busy || areObjectsEqual(filter, this.filter)) {
                    return;
                }
                this.entries = null;
                this.filter = filter;
                this.fetchNextPage(true);
            },
            toggleSort(by) {
                if (this.busy) {
                    return;
                }
                if (this.sort.by === by) {
                    this.sort.descending = !this.sort.descending;
                } else {
                    this.sort.by = by;
                    this.sort.options = this.SORT_OPTIONS[by] || {};
                    this.sort.descending = typeof this.sort.options.defaultDescending === 'boolean' ? this.sort.options.defaultDescending : false;
                }
                this.fetchNextPage(true);
            },
            async fetchNextPage(clean) {
                const request = {
                    sortBy: this.sort.by,
                    sortDescending: this.sort.descending ? '1' : '0',
                    after: !clean && this.entries?.length ? this.entries[this.entries.length - 1].id.toString() : '',
                }
                for (const [filterKey, filterValue] of Object.entries(this.filter)) {
                    request[filterKey] = filterValue;
                }
                this.loadError = null;
                this.busy = true;
                let response;
                try {
                    response = await fetchJson(<?= json_encode((string) $view->action('getNextPage')) ?>, <?= json_encode($token->generate('ua-broken-nextpage')) ?>, request);
                } catch (e) {
                    this.loadError = e?.message || e?.toString() || <?= json_encode(t('Unknown error')) ?>;
                    return;
                } finally {
                    this.busy = false;
                }
                this.hasMoreEntries = response.hasMore;
                if (clean || this.entries === null) {
                    this.entries = [];
                }
                response.entries.forEach((entry) => {
                    this.entries.push(this.finalizeEntry(entry));
                });
            },
            finalizeEntry(entry) {
                if (entry.fullPath === undefined) {
                    entry.fullPath = '/' + entry.path;
                    if (entry.querystring !== '') {
                        entry.fullPath += '?' + entry.querystring;
                    }
                }
                ['firstHit', 'lastHit'].forEach((field) => {
                    if (typeof entry[field] === 'number') {
                        const d = new Date();
                        d.setTime(entry[field] * 1000);
                        entry[field] = d;
                    }
                });
                return entry;
            },
            askDeleteEntry(entry) {
                if (this.busy) {
                    return;
                }
                ConcreteAlert.confirm(
                    <?= json_encode(t('Are you sure you want to delete this entry?')) ?>,
                    () => {
                        if (this.busy) {
                            return;
                        }
                        this.deleteEntry(entry, (success) => {
                            if (success) {
                                jQuery.fn.dialog.closeTop();
                            }
                        });
                    },
                    'btn-danger',
                    <?= json_encode(t('Delete')) ?>
                );
            },
            async deleteEntry(entry, callback) {
                if (!callback) {
                    callback = () => {};
                }
                if (this.busy) {
                    callback(false);
                    return;
                }
                this.busy = true;
                try {
                    await fetchJson(<?= json_encode((string) $view->action('deleteOne')) ?>, <?= json_encode($token->generate('ua-broken-deleteone')) ?>, {id: entry.id});
                } catch (e) {
                    ConcreteAlert.error({message: e?.message || e?.toString() || <?= json_encode(t('Unknown error')) ?>});
                    callback(false);
                    return;
                } finally {
                    this.busy = false;
                }
                const index = this.entries.indexOf(entry);
                if (index >= 0) {
                    this.entries.splice(index, 1);
                }
                callback(true);
            },
            async deleteAll(callback) {
                if (!callback) {
                    callback = () => {};
                }
                if (this.busy) {
                    callback(false);
                    return;
                }
                this.busy = true;
                try {
                    await fetchJson(<?= json_encode((string) $view->action('deleteAll')) ?>, <?= json_encode($token->generate('ua-broken-deleteall')) ?>);
                } catch (e) {
                    ConcreteAlert.error({message: e?.message || e?.toString() || <?= json_encode(t('Unknown error')) ?>});
                    callback(false);
                    return;
                } finally {
                    this.busy = false;
                }
                this.availableMethods.splice(0, this.availableMethods.length);
                this.entries = null;
                callback(true);
            },
            formatDateTime(dt) {
                return dt instanceof Date ? DATETIME_FORMAT.format(dt) : '';
            },
            formatNumber(num) {
                return typeof num === 'number' ? NUMBER_FORMAT.format(num) : '';
            },
        },
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ready);
} else {
    ready();
}

})();</script>
