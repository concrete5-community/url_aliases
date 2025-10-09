<?php

declare(strict_types=1);

use Punic\Unit;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Validation\CSRF\Token $token
 * @var Concrete\Core\Page\View\PageView $view
 * @var Concrete\Package\UrlAliases\Controller\SinglePage\Dashboard\System\UrlAliases\Options $controller
 * @var Concrete\Core\Form\Service\Form $form
 * @var bool $log404Enabled
 * @var string $log404ExcludePathRXCustom
 * @var bool $log404ExcludePathRXUseDefault
 * @var bool|string $log404LogQueryString
 * @var int $log404entryMaxAge
 * @var string $rootUrl
 */

?>
<form method="POST" action="<?= h((string) $view->action('save')) ?>">
    <?php $token->output('ua-options-save') ?>

    <h4><?= t('Not Found Requests') ?></h4>
    
    <p>
        <?= t('Here you can configure whenever and how the requests that still result in 404 - Page Not Found errors even after checking the URL aliases') ?>
    </p>
    
    <div class="form-group form-check">
        <?= $form->checkbox('log404Enabled', '1', $log404Enabled) ?>
        <?= $form->label('log404Enabled', t('Log requests that results in a 404 - Page Not found errors'), ['class' => 'form-check-label']) ?>
    </div>
    
    <div id="log404Enabled-options"<?= $log404Enabled ? '' : ' style="display: none"' ?>>
        <div class="form-group">
            <?= $form->label('log404ExcludePathRXCustom', t("Don't log requests whose path matches any of the following regular expressions") . ' <a href="#" class="btn btn-sm btn-xs btn-secondary btn-default" id="log404-excludepathrx-test-launcher">' . t('Test') . '</a>') ?>
            <?= $form->textarea('log404ExcludePathRXCustom', $log404ExcludePathRXCustom, ['spellcheck' => 'false', 'style' => "min-height: 200px; font-family: SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace"]) ?>
            <div class="small text-muted">
                <?= t('One regular expression per line.') ?>
                <?= t('The paths will always be lower case and will never have leading or trailing slashes (%s).', '<code>/</code>') ?>
            </div>
            <div class="form-check">
                <?= $form->checkbox('log404ExcludePathRXUseDefault', '1', $log404ExcludePathRXUseDefault) ?>
                <?= $form->label('log404ExcludePathRXUseDefault', t('Also use the default exclusion rules (exclude .php files except index.php)'), ['class' => 'form-check-label']) ?>
            </div>
        </div>
        
        <div class="form-group">
            <?= $form->label('', t('Log querystring parameters')) ?>
            <div class="form-check">
                <?= $form->radio('log404LogQueryString', 'none', $log404LogQueryString === false ? 'none' : 'x', ['id' => 'log404LogQueryString-none']) ?>
                <?= $form->label('log404LogQueryString-none', t("Don't log any querystring parameter"), ['class' => 'form-check-label']) ?>
            </div>
            <div class="form-check">
                <?= $form->radio('log404LogQueryString', 'all', $log404LogQueryString === true ? 'all' : 'x', ['id' => 'log404LogQueryString-all']) ?>
                <?= $form->label('log404LogQueryString-all', t('Log every querystring parameter'), ['class' => 'form-check-label']) ?>
            </div>
            <div class="form-check">
                <?= $form->radio('log404LogQueryString', 'some', is_string($log404LogQueryString) ? 'some' : 'x', ['id' => 'log404LogQueryString-some']) ?>
                <?= $form->label('log404LogQueryString-some', t('Log only selected querystring parameters'), ['class' => 'form-check-label']) ?>
                <div id="log404LogQueryString-some-list"<?= is_string($log404LogQueryString) ? '' : ' style="display: none"' ?>>
                    <?= $form->textarea('log404LogQueryStringList', is_string($log404LogQueryString) ? $log404LogQueryString : '', ['spellcheck' => 'false', 'style' => "min-height: 200px; font-family: SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace"]) ?>
                    <div class="small text-muted">
                        <?= t('One parameter per line.') ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <?= $form->label('', t('Automatic logged entries removal')) ?>
            <div class="form-check">
                <?= $form->radio('log404entryMaxAge', 'none', $log404entryMaxAge <= 0 ? 'none' : 'x', ['id' => 'log404entryMaxAge-none']) ?>
                <?= $form->label('log404entryMaxAge-none', t('Keep entries until manually deleted'), ['class' => 'form-check-label']) ?>
            </div>
            <div class="form-check">
                <?= $form->radio('log404entryMaxAge', 'set', $log404entryMaxAge > 0 ? 'set' : 'x', ['id' => 'log404entryMaxAge-set']) ?>
                <?= $form->label('log404entryMaxAge-set', t('Automatically delete entries older than'), ['class' => 'form-check-label']) ?>
                <?php
                $log404entryMaxAgeSeconds = $log404entryMaxAge > 0 ? $log404entryMaxAge : 604800;
                $log404entryMaxAgeDays = intdiv($log404entryMaxAgeSeconds, 86400);
                $log404entryMaxAgeSeconds -= $log404entryMaxAgeDays * 86400;
                $log404entryMaxAgeHours = intdiv($log404entryMaxAgeSeconds, 3600);
                $log404entryMaxAgeSeconds -= $log404entryMaxAgeHours * 3600;
                $log404entryMaxAgeMinutes = intdiv($log404entryMaxAgeSeconds, 60);
                $log404entryMaxAgeSeconds -= $log404entryMaxAgeMinutes * 60;
                ?>
                <div class="input-group input-group-sm" style="max-width: 600px; <?= $log404entryMaxAge > 0 ? '' : ' display: none' ?>">
                    <?= $form->number('log404entryMaxAge-days', $log404entryMaxAgeDays, ['placeholder' => Unit::getName('duration/day', 'long'), 'min' => '0', 'step' => '1']) ?>
                    <span class="input-group-text input-group-addon"><?= Unit::getName('duration/day', 'short') ?></span>
                    <?= $form->number('log404entryMaxAge-hours', $log404entryMaxAgeHours, ['placeholder' => Unit::getName('duration/hour', 'long'), 'min' => '0', 'step' => '1']) ?>
                    <span class="input-group-text input-group-addon"><?= Unit::getName('duration/hour', 'short') ?></span>
                    <?= $form->number('log404entryMaxAge-minutes', $log404entryMaxAgeMinutes, ['placeholder' => Unit::getName('duration/minute', 'long'), 'min' => '0', 'step' => '1']) ?>
                    <span class="input-group-text input-group-addon"><?= Unit::getName('duration/minute', 'short') ?></span>
                    <?= $form->number('log404entryMaxAge-seconds', $log404entryMaxAgeSeconds, ['placeholder' => Unit::getName('duration/second', 'long'), 'min' => '0', 'step' => '1']) ?>
                    <span class="input-group-text input-group-addon"><?= Unit::getName('duration/second', 'short') ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <button type="submit" class="btn btn-primary pull-right float-end"><?= t('Save') ?></button>
        </div>
    </div>

</form>

<div style="display: none">
    <div id="log404-excludepathrx-test">
        <div class="input-group input-group-sm">
            <span class="input-group-addon input-group-text"><?= h($rootUrl) ?></span>
            <input class="form-control" id="log404-excludepathrx-test-path" type="text" placeholder="<?= t('Path') ?>" v-model="path" v-bind:readonly="busy" v-on:keyup.enter.prevent="test()" />
            <span class="input-group-btn">
                <button class="btn btn-primary" v-on:click.prevent="test()" v-bind:disabled="busy">
                    <?= t('Check') ?>
                </button>
            </span>
        </div>
        <div>
            <br /><br />
            <ul class="list-unstyled">
                <li>
                    <span v-if="pathSatisfiesDefaultRules === null">
                        &#x25EF; <!-- LARGE CIRCLE -->
                    </span>
                    <span v-else-if="pathSatisfiesDefaultRules === false">
                        &#x1F534; <!-- LARGE RED CIRCLE -->
                    </span>
                    <span v-else-if="pathSatisfiesDefaultRules === true">
                        &#x1F7E2; <!-- LARGE GREEN CIRCLE -->
                    </span>
                    <?= t('If custom rules are enabled, the path will be excluded') ?>
                </li>
                <li v-if="customRules !== ''">
                    <span v-if="pathSatisfiesCustomRules === null">
                        &#x25EF; <!-- LARGE CIRCLE -->
                    </span>
                    <span v-else-if="pathSatisfiesCustomRules === false">
                        &#x1F534; <!-- LARGE RED CIRCLE -->
                    </span>
                    <span v-else-if="pathSatisfiesCustomRules === true">
                        &#x1F7E2; <!-- LARGE GREEN CIRCLE -->
                    </span>
                    <?= t('The path will be excluded because it satisfies your custom rules') ?>
                </li>
            </ul>
            <div v-if="normalizedPath && normalizedPath !== path" class="small text-muted">
                <?= t('PS: the path has been normalized as %s', '<br /><code>{{ normalizedPath }}</code>') ?>
            </div>
        </div>
        <div class="dialog-buttons">
            <button class="btn btn-secondary pull-right" data-dialog-action="cancel" v-bind:disabled="busy"><?= t('Close') ?></button>
        </div>
    </div>
</div>

<script>
(function() {

function updateView()
{
    const log404Enabled = document.querySelector('input[name="log404Enabled"]')?.checked;
    document.querySelector('#log404Enabled-options').style.display = log404Enabled ? '' : 'none';
    
    const log404LogQueryString = document.querySelector('input[name="log404LogQueryString"]:checked')?.value;
    document.querySelector('#log404LogQueryString-some-list').style.display = log404LogQueryString === 'some' ? '' : 'none';
    
    const log404entryMaxAge = document.querySelector('input[name="log404entryMaxAge"]:checked')?.value;
    document.querySelector('input[name="log404entryMaxAge-seconds"]').closest('.input-group').style.display = log404entryMaxAge === 'set' ? '' : 'none';
}

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

function ready() {
    document.querySelectorAll('input[name="log404Enabled"], input[name="log404LogQueryString"], input[name="log404entryMaxAge"]').forEach((i) => i.addEventListener('change', () => updateView()));
    updateView();

    new Vue({
        el: '#log404-excludepathrx-test',
        data() {
            return {
                busy: false,
                path: '',
                normalizedPath: null,
                customRules: '',
                pathSatisfiesDefaultRules: null,
                pathSatisfiesCustomRules: null,
            };
        },
        mounted() {
            document.querySelector('#log404-excludepathrx-test-launcher').addEventListener('click', (e) => {
                e.preventDefault();
                this.show();
            });
        },
        watch: {
            path() {
                this.reset();
            },
        },
        methods: {
            async show() {
                if (this.busy) {
                    return;
                }
                this.reset();
                if (this.customRules !== '') {
                    this.busy = true;
                    try {
                        await fetchJson(
                            <?= json_encode((string) $view->action('testExlcudePathRXCustom')) ?>,
                            <?= json_encode($token->generate('ua-options-testexlcudepathrxcustom')) ?>,
                            {
                                customRules: this.customRules,
                            }); 
                    } catch (e) {
                        ConcreteAlert.error({message: e?.message || e?.toString() || <?= json_encode(t('Unknown error')) ?>});
                        return;
                    } finally {
                        this.busy = false;
                    }
                }
                jQuery.fn.dialog.open({
                    element: this.$el,
                    modal: true,
                    width: 600,
                    title: <?= json_encode(t('Test')) ?>,
                    height: 'auto',
                });
            },
            reset() {
                this.customRules = document.querySelector('#log404ExcludePathRXCustom').value.split(/^\s+|\s*[\r\n]+\s*|\s+$/).filter(Boolean).join('\n'),
                this.normalizedPath = null;
                this.pathSatisfiesDefaultRules = null;
                this.pathSatisfiesCustomRules = null;
            },
            async test() {
                if (this.busy) {
                    return;
                }
                this.reset();
                this.busy = true;
                let result;
                try {
                    result = await fetchJson(
                    <?= json_encode((string) $view->action('testExlcudePathRX')) ?>,
                    <?= json_encode($token->generate('ua-options-testexlcudepathrx')) ?>,
                    {
                        path: this.path,
                        customRules: this.customRules,
                    }); 
                } catch (e) {
                    ConcreteAlert.error({message: e?.message || e?.toString() || <?= json_encode(t('Unknown error')) ?>});
                    return;
                } finally {
                    this.busy = false;
                }
                this.normalizedPath = result.normalizedPath;
                this.pathSatisfiesDefaultRules = result.pathSatisfiesDefaultRules;
                this.pathSatisfiesCustomRules = result.pathSatisfiesCustomRules;
                
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
