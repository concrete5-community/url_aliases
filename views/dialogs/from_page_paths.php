<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\View\DialogView $view
 * @var Concrete\Package\UrlAliases\Controller\Dialog\FromPagePaths $controller
 * @var array $additionalPaths
 */

?>
<div id="ua-from-page-paths-app" style="tab-index: -1" v-cloak>
    <div class="alert alert-info alert-dismissible">
        <button type="button" class="btn-close close" data-bs-dismiss="alert" data-dismiss="alert"><?= version_compare(APP_VERSION, '9') < 0 ? '&times;' : '' ?></button>
        <?= t('Here you can find the additional paths defined for all the website pages.') ?><br />
        <?= t('You can choose to use URL Aliases instead of these additional paths, so that you have just one central place (URL Aliases) where you can manage all the URL aliases.') ?>
    </div>
    <div v-if="additionalPaths.length === 0" class="alert alert-info">
        <?= t('No page found with additional paths.') ?>
    </div>
    <div v-else style="max-height: 50vh; min-height: 100px; overflow: auto">
        <table class="table table-striped table-sm table-condensed table-hover">
            <colgroup>
                <col />
                <col />
                <col width="1" />
            </colgroup>
            <thead>
                <tr>
                    <th><?= t('Page') ?></th>
                    <th><?= t('Additional Path') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="ap in additionalPaths" v-bind:key="ap.id">
                    <td>
                        <a target="_blank" v-bind:href="ap.canonicalUrl">{{ ap.pageName }}</a>
                    </td>
                    <td>
                        <code>{{ ap.additionalPath }}</code>
                    </td>
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-xs btn-primary" v-on:click.prevent="convertToUrlAlias(ap)" v-bind:disabled="busy">
                            <?= t('Convert to URL Alias') ?>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="dialog-buttons">
        <button class="btn btn-secondary pull-right" v-on:click.prevent="close()"><?= t('Close') ?></button>
    </div>
</div>
<script>
(function() {

function ready() {
    new Vue({
        el: '#ua-from-page-paths-app',
        data() {
            return {
                busy: false,
                additionalPaths: <?= json_encode($additionalPaths) ?>,
            }
        },
        methods: {
            close() {
                if (!this.busy) {
                    jQuery.fn.dialog.closeTop();
                }
            },
            convertToUrlAlias(ap) {
                if (this.busy) {
                    return;
                }
                this.busy = true;
                const ev = new CustomEvent('ccm.url_aliases.convertPagePathToUrlAlias', {
                    detail: {
                        data: ap.id,
                        done: (success) => {
                            this.busy = false;
                            if (success) {
                                this.convertedToUrlAlias(ap);
                            }
                        },
                    },
                });
                window.dispatchEvent(ev);
            },            
            convertedToUrlAlias(ap) {
                const index = this.additionalPaths.indexOf(ap);
                if (index >= 0) {
                    this.additionalPaths.splice(index, 1);
                }
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
