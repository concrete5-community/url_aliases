<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Package\UrlAliases\Controller\Dialog\EditLocalizedTarget $controller
 * @var Concrete\Core\View\DialogView $view
 * @var Concrete\Core\Form\Service\Form $form
 * @var Concrete\Core\Form\Service\DestinationPicker\DestinationPicker $destinationPicker
 * @var array $targetDestinationPickerConfig
 * @var string $rootUrl
 * @var Concrete\Package\UrlAliases\Entity\UrlAlias\LocalizedTarget $localizedTarget
 * @var bool $asNew
 */

ob_start();
?>
<form id="ua-localizedtarget-editing" v-cloak>
    <ua-accept-header-builder
        v-bind:allow-any="true"
        v-bind:language="language" v-on:update-language="language = $event"
        v-bind:script="script" v-on:update-script="script = $event"
        v-bind:territory="territory" v-on:update-territory="territory = $event"
    >
    </ua-accept-header-builder>
    <div class="form-group">
        <label class="form-label"><?= t('Target') ?></label>
        <?= $destinationPicker->generate('target', $targetDestinationPickerConfig, $localizedTarget->getTargetType(), $localizedTarget->getTargetValue()) ?>
    </div>
    <div class="form-group" v-if="askFragmentIdentifier">
        <label class="form-label" for="ua-localizedtarget-editing-fragmentidentifier">
            <?= t('Point in the page where users should be redirected to') ?>
        </label>
        <input class="form-control" id="ua-localizedtarget-editing-fragmentidentifier" type="text" maxlength="255" spellcheck="false" v-model.trim="fragmentIdentifier" />
        <div class="small text-muted"><?= t('Specify the value after the %s character', '<code>#</code>') ?></div>
    </div>
    <div class="dialog-buttons">
        <button class="btn btn-secondary pull-left" v-on:click.prevent="cancel()"><?= t('Cancel') ?></button>
        <?php
        if ($localizedTarget->getID() !== null) {
            ?>
            <button class="btn btn-danger pull-right" v-on:click.prevent="deleteLocalizedTarget()"><?= t('Delete') ?></button>
            <?php
        }
        ?>
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
        el: '#ua-localizedtarget-editing',
        data() {
            return {
                language: <?= json_encode($localizedTarget->getLanguage()) ?>,
                script: <?= json_encode($localizedTarget->getScript()) ?>,
                territory: <?= json_encode($localizedTarget->getTerritory()) ?>,
                askFragmentIdentifier: false,
                fragmentIdentifier: <?= json_encode($localizedTarget->getFragmentIdentifier()) ?>,
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
        methods: {
            cancel() {
                jQuery.fn.dialog.closeTop();
            },
            <?php
            if ($localizedTarget->getID() !== null) {
                ?>
                deleteLocalizedTarget() {
                    const ev = new CustomEvent('ccm.url_aliases.deleteLocalizedTarget', {
                        detail: {
                            data: {
                                urlAlias: <?= json_encode($localizedTarget->getUrlAlias()->getID()) ?>,
                                id: <?= json_encode($localizedTarget->getID()) ?>,
                            },
                            done() {
                                jQuery.fn.dialog.closeTop();
                            },
                        },
                    });
                    window.dispatchEvent(ev);
                },
                <?php
            }
            ?>
            save() {
                const data = {
                    urlAlias: <?= json_encode($localizedTarget->getUrlAlias()->getID()) ?>,
                    id: <?= json_encode($localizedTarget->getID() ?? 'new') ?>,
                    language: this.language,
                    script: this.script,
                    territory: this.territory,
                    targetType: this.$el.querySelector(':scope [name="target__which"]').value,
                    fragmentIdentifier: this.fragmentIdentifier,
                };
                data.targetValue = this.$el.querySelector(`:scope [name="target_${data.targetType}"]`).value;
                const ev = new CustomEvent('ccm.url_aliases.saveLocalizedTarget', {
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