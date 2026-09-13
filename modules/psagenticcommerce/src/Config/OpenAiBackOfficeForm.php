<?php

namespace PrestaShopAgenticCommerce\Config;

use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiFeedConfigResolver;
use PrestaShopAgenticCommerce\Export\OpenAi\OpenAiSftpConfigResolver;
use PrestaShopAgenticCommerce\Install\OpenAiConfigInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class OpenAiBackOfficeForm
{
    /** @param array<string,string> $values */
    public function render(\Module $module, array $values): string
    {
        $values[OpenAiConfigInstaller::CRON_TOKEN] = (string) \Configuration::get(OpenAiConfigInstaller::CRON_TOKEN);
        $helper = new \HelperForm();
        $helper->module = $module;
        $helper->name_controller = $module->name;
        $helper->token = \Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = \AdminController::$currentIndex . '&configure=' . $module->name;
        $helper->submit_action = 'submitPsAgenticOpenAi';
        $helper->default_form_language = (int) \Configuration::get('PS_LANG_DEFAULT');
        $helper->fields_value = $values;

        return $helper->generateForm([
            $this->merchantForm($module),
            $this->sftpForm($module),
        ]);
    }

    /** @return array<string,mixed> */
    private function merchantForm(\Module $module): array
    {
        return [
            'form' => [
                'legend' => ['title' => $module->trans('OpenAI Merchant Feed', [], 'Modules.Psagenticcommerce.Admin'), 'icon' => 'icon-shopping-cart'],
                'description' => $module->trans('These values are stored per shop and used to build the OpenAI full product snapshot.', [], 'Modules.Psagenticcommerce.Admin'),
                'input' => [
                    $this->text(OpenAiFeedConfigResolver::SELLER_NAME, $module->trans('Seller name', [], 'Modules.Psagenticcommerce.Admin'), true),
                    $this->text(OpenAiFeedConfigResolver::SELLER_URL, $module->trans('Seller URL', [], 'Modules.Psagenticcommerce.Admin'), true),
                    $this->text(OpenAiFeedConfigResolver::RETURN_POLICY_URL, $module->trans('Return policy URL', [], 'Modules.Psagenticcommerce.Admin'), true),
                    $this->text(OpenAiFeedConfigResolver::TARGET_COUNTRIES, $module->trans('Target countries', [], 'Modules.Psagenticcommerce.Admin'), true, 'TR,DE,US'),
                    $this->text(OpenAiFeedConfigResolver::STORE_COUNTRY, $module->trans('Store country', [], 'Modules.Psagenticcommerce.Admin'), true, 'TR'),
                    $this->text(OpenAiFeedConfigResolver::DEFAULT_BRAND, $module->trans('Default brand', [], 'Modules.Psagenticcommerce.Admin'), false),
                    $this->switchField(OpenAiFeedConfigResolver::ELIGIBLE_SEARCH, $module->trans('Eligible for search', [], 'Modules.Psagenticcommerce.Admin')),
                    $this->switchField(OpenAiFeedConfigResolver::ELIGIBLE_CHECKOUT, $module->trans('Eligible for checkout', [], 'Modules.Psagenticcommerce.Admin')),
                    $this->text(OpenAiFeedConfigResolver::PRIVACY_POLICY_URL, $module->trans('Privacy policy URL', [], 'Modules.Psagenticcommerce.Admin'), false),
                    $this->text(OpenAiFeedConfigResolver::TERMS_URL, $module->trans('Terms URL', [], 'Modules.Psagenticcommerce.Admin'), false),
                    [
                        'type' => 'select',
                        'label' => $module->trans('Ads eligibility', [], 'Modules.Psagenticcommerce.Admin'),
                        'name' => OpenAiFeedConfigResolver::ADS_ELIGIBLE,
                        'options' => [
                            'query' => [
                                ['id' => '', 'name' => $module->trans('Not specified', [], 'Modules.Psagenticcommerce.Admin')],
                                ['id' => '1', 'name' => $module->trans('Eligible', [], 'Modules.Psagenticcommerce.Admin')],
                                ['id' => '0', 'name' => $module->trans('Not eligible', [], 'Modules.Psagenticcommerce.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function sftpForm(\Module $module): array
    {
        return [
            'form' => [
                'legend' => ['title' => $module->trans('OpenAI SFTP Delivery', [], 'Modules.Psagenticcommerce.Admin'), 'icon' => 'icon-cloud-upload'],
                'description' => $module->trans('Enter the SFTP values provided during merchant onboarding. Host-key verification is mandatory.', [], 'Modules.Psagenticcommerce.Admin'),
                'input' => [
                    $this->switchField(OpenAiSftpConfigResolver::ENABLED, $module->trans('Enable SFTP delivery', [], 'Modules.Psagenticcommerce.Admin')),
                    $this->text(OpenAiSftpConfigResolver::HOST, $module->trans('SFTP host', [], 'Modules.Psagenticcommerce.Admin'), false),
                    $this->text(OpenAiSftpConfigResolver::PORT, $module->trans('SFTP port', [], 'Modules.Psagenticcommerce.Admin'), false, '22'),
                    $this->text(OpenAiSftpConfigResolver::USERNAME, $module->trans('Username', [], 'Modules.Psagenticcommerce.Admin'), false),
                    [
                        'type' => 'select',
                        'label' => $module->trans('Authentication', [], 'Modules.Psagenticcommerce.Admin'),
                        'name' => OpenAiSftpConfigResolver::AUTH_MODE,
                        'options' => [
                            'query' => [
                                ['id' => 'secret', 'name' => $module->trans('Account secret', [], 'Modules.Psagenticcommerce.Admin')],
                                ['id' => 'public_key', 'name' => $module->trans('SSH public key', [], 'Modules.Psagenticcommerce.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'password',
                        'label' => $module->trans('Authentication secret', [], 'Modules.Psagenticcommerce.Admin'),
                        'name' => 'PSAGENTIC_OPENAI_SFTP_SECRET_INPUT',
                        'desc' => $module->trans('Leave blank to keep the currently stored encrypted value.', [], 'Modules.Psagenticcommerce.Admin'),
                    ],
                    $this->switchField('PSAGENTIC_OPENAI_SFTP_CLEAR_SECRET', $module->trans('Clear stored authentication secret', [], 'Modules.Psagenticcommerce.Admin')),
                    $this->text(OpenAiSftpConfigResolver::PRIVATE_KEY_FILE, $module->trans('Private key file path', [], 'Modules.Psagenticcommerce.Admin'), false),
                    $this->text(OpenAiSftpConfigResolver::PUBLIC_KEY_FILE, $module->trans('Public key file path', [], 'Modules.Psagenticcommerce.Admin'), false),
                    [
                        'type' => 'password',
                        'label' => $module->trans('Private-key passphrase', [], 'Modules.Psagenticcommerce.Admin'),
                        'name' => 'PSAGENTIC_OPENAI_SFTP_KEY_SECRET_INPUT',
                        'desc' => $module->trans('Leave blank to keep the currently stored encrypted value.', [], 'Modules.Psagenticcommerce.Admin'),
                    ],
                    $this->switchField('PSAGENTIC_OPENAI_SFTP_CLEAR_KEY_SECRET', $module->trans('Clear stored key passphrase', [], 'Modules.Psagenticcommerce.Admin')),
                    $this->text(OpenAiSftpConfigResolver::REMOTE_PATH, $module->trans('Remote file path', [], 'Modules.Psagenticcommerce.Admin'), false, 'products.jsonl.gz'),
                    $this->text(OpenAiSftpConfigResolver::HOST_KEY_SHA256, $module->trans('SSH host key SHA256', [], 'Modules.Psagenticcommerce.Admin'), false),
                    $this->text(OpenAiSftpConfigResolver::HOST_KEY_MD5, $module->trans('SSH host key MD5 fallback', [], 'Modules.Psagenticcommerce.Admin'), false),
                    $this->text(OpenAiSftpConfigResolver::KNOWN_HOSTS_FILE, $module->trans('known_hosts file path', [], 'Modules.Psagenticcommerce.Admin'), false),
                    $this->text(OpenAiSftpConfigResolver::TIMEOUT, $module->trans('Transfer timeout (seconds)', [], 'Modules.Psagenticcommerce.Admin'), false, '60'),
                    [
                        'type' => 'text',
                        'label' => $module->trans('Cron token', [], 'Modules.Psagenticcommerce.Admin'),
                        'name' => OpenAiConfigInstaller::CRON_TOKEN,
                        'readonly' => true,
                        'desc' => $module->trans('Use this only in the X-Agentic-Cron-Token HTTP header. Do not put it in the cron URL.', [], 'Modules.Psagenticcommerce.Admin'),
                    ],
                ],
                'submit' => [
                    'title' => $module->trans('Save settings', [], 'Modules.Psagenticcommerce.Admin'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function text(string $name, string $label, bool $required, string $placeholder = ''): array
    {
        return ['type' => 'text', 'label' => $label, 'name' => $name, 'required' => $required, 'placeholder' => $placeholder];
    }

    /** @return array<string,mixed> */
    private function switchField(string $name, string $label): array
    {
        return [
            'type' => 'switch',
            'label' => $label,
            'name' => $name,
            'is_bool' => true,
            'values' => [
                ['id' => $name . '_on', 'value' => 1, 'label' => 'Yes'],
                ['id' => $name . '_off', 'value' => 0, 'label' => 'No'],
            ],
        ];
    }
}
