<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Package\UrlAliases\Controller\Dialog\EditUrlAlias $controller
 * @var Concrete\Core\View\DialogView $view
 * @var Concrete\Core\Form\Service\Form $form
 * @var Concrete\Core\Form\Service\DestinationPicker\DestinationPicker $destinationPicker
 * @var array $targetDestinationPickerConfig
 * @var string $rootUrl
 * @var Concrete\Package\UrlAliases\Entity\UrlAlias $urlAlias
 * @var bool $asNew
 */

$isNew = $asNew || $urlAlias->getID() === null;

ob_start();
?>
<form id="ua-urlalias-editing" v-cloak>
    <div class="form-group mb-3">
        <label for="ua-urlalias-editing-pathandquerystring" class="form-label"><?= t('Alias URL') ?></label>
        <div class="input-group">
            <span class="input-group-addon input-group-text"><?= h($rootUrl) ?></span>
            <input type="text" class="form-control" v-model.trim="pathAndQuerystring" id="ua-urlalias-editing-pathandquerystring" spellcheck="false" />
        </div>
    </div>
    <div class="form-group mb-3">
        <label class="form-label"><?= t('Target') ?></label>
        <?= $destinationPicker->generate('target', $targetDestinationPickerConfig, $urlAlias->getTargetType(), $urlAlias->getTargetValue()) ?>
    </div>
    <div class="form-group" v-if="askFragmentIdentifier">
        <label class="form-label" for="ua-urlalias-editing-fragmentidentifier">
            <?= t('Point in the page where users should be redirected to') ?>
        </label>
        <input class="form-control" id="ua-urlalias-editing-fragmentidentifier" type="text" maxlength="255" spellcheck="false" v-model.trim="fragmentIdentifier" />
        <div class="small text-muted"><?= t('Specify the value after the %s character', '<code>#</code>') ?></div>
    </div>
    <div class="form-group">
        <label class="form-label"><?= t('Options') ?></label>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" v-model="enabled" id="ua-urlalias-editing-enabled">
            <label class="form-check-label" for="ua-urlalias-editing-enabled">
                <?= t('Enabled') ?>
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" v-model="acceptAdditionalQuerystringParams" id="ua-urlalias-editing-acceptadditionalquerystringparams" />
            <label class="form-check-label" for="ua-urlalias-editing-acceptadditionalquerystringparams">
                <span v-if="hasQuerystring">
                    <?= t('Accept additional querystring parameters') ?>
                </span>
                <span v-else>
                    <?= t('Accept querystring parameters') ?>
                </span>
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" v-model="forwardQuerystringParams" id="ua-urlalias-editing-forwardquerystringparams" v-bind:disabled="!acceptAdditionalQuerystringParams">
            <label class="form-check-label" for="ua-urlalias-editing-forwardquerystringparams">
                <?= t('Forward received querystring parameters') ?>
            </label>
        </div>
    </div>
    <div class="dialog-buttons">
        <button class="btn btn-secondary pull-left" v-on:click.prevent="cancel()"><?= t('Cancel') ?></button>
        <button class="btn btn-primary pull-right" v-on:click.prevent="save()"><?= t('Save') ?></button>
    </div>
</form>
<?php
$template = ob_get_contents();
ob_end_clean();
$scripts = [];

$template = preg_replace_callback(
    '#<script\b[^>]*>(.*?)</script>#is',
    static function (array $matches) use (&$scripts) {
        $scripts[] = trim($matches[1]);

        return '';
    },
    $template
);

echo $template;
?>
<script>
(function() {

let myVueApp = null;

function destinationPickerHook()
{
    const select = myVueApp?.$el?.querySelector(':scope [name="target__which"]');
    if (!select) {
        return;
    }
    switch (select.value) {
        case 'page':
            myVueApp.askFragmentIdentifier = true;
            break;
        default:
            myVueApp.askFragmentIdentifier = false;
            break;
    }
}

function ready() {
    myVueApp = new Vue({
        el: '#ua-urlalias-editing',
        data() {
            return {
                pathAndQuerystring: <?= json_encode($urlAlias->getPathAndQuerystring()) ?>,
                acceptAdditionalQuerystringParams: <?= json_encode($urlAlias->isAcceptAdditionalQuerystringParams()) ?>,
                forwardQuerystringParams: <?= json_encode($urlAlias->isForwardQuerystringParams()) ?>,
                enabled: <?= json_encode($urlAlias->isEnabled()) ?>,
                askFragmentIdentifier: false,
                fragmentIdentifier: <?= json_encode($urlAlias->getFragmentIdentifier()) ?>,
            };
        },
        mounted() {
            <?= implode("\n", $scripts) ?>;
            this.$el.querySelector(':scope [name="target__which"]').addEventListener('change', destinationPickerHook);
            setTimeout(() => destinationPickerHook(), 100);
        },
        beforeDestroy() {
            this.$el.querySelector(':scope [name="target__which"]')?.removeEventListener('change', destinationPickerHook);
        },
        computed: {
            hasQuerystring() {
                return this.parseFinalPath().querystring !== '';
            },
        },
        methods: {
            parseFinalPath() {
                const result = {
                    path: this.pathAndQuerystring.replace(/^\/+/, ''),
                    querystring: '',
                    errors: [],
                };
                const hashChunks = result.path.split('#', 2);
                if (hashChunks.length !== 1) {
                    result.errors.push(<?= json_encode(t("The alias can't contain the character \"%s\"", '#')) ?>);
                    result.path = hashChunks[0];
                }
                const pQuestion = result.path.indexOf('?');
                if (pQuestion >= 0) {
                    result.querystring = result.path.substring(pQuestion + 1);
                    result.path = result.path.substring(0, pQuestion);
                }
                result.path = result.path.replace(/\/+$/, '');
                if (result.path === '') {
                    result.errors.push(<?= json_encode(t('Please specify the path of the alias')) ?>);
                }
                return result;
            },
            cancel() {
                jQuery.fn.dialog.closeTop();
            },
            save() {
                const finalPath = this.parseFinalPath();
                if (finalPath.errors.length !== 0) {
                    ConcreteAlert.error({message: finalPath.errors.join('\n')});
                    return;
                }
                const data = {
                    id: <?= json_encode($isNew ? 'new' : $urlAlias->getID()) ?>,
                    path: finalPath.path,
                    querystring: finalPath.querystring,
                    targetType: this.$el.querySelector(':scope [name="target__which"]').value,
                    fragmentIdentifier: this.fragmentIdentifier,
                    acceptAdditionalQuerystringParams: this.acceptAdditionalQuerystringParams,
                    enabled: this.enabled,
                    forwardQuerystringParams: this.forwardQuerystringParams,
                };
                data.targetValue = this.$el.querySelector(`:scope [name="target_${data.targetType}"]`).value;
                const ev = new CustomEvent('ccm.url_aliases.saveUrlAlias', {
                    detail: {
                        data,
                        done(success) {
                            jQuery.fn.dialog.hideLoader();
                            if (success) {
                                jQuery.fn.dialog.closeTop();
                            }
                        },
                    },
                });
                jQuery.fn.dialog.showLoader();
                window.dispatchEvent(ev);
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