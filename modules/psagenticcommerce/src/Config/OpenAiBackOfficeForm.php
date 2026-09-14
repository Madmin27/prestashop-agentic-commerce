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
                'legend' => ['title' => $module->translateAdmin('OpenAI Merchant Feed', [], 'Modules.Psagenticcommerce.Admin'), 'icon' => 'icon-shopping-cart'],
                'description' => $module->translateAdmin('These values are stored per shop and used to build the OpenAI full product snapshot.', [], 'Modules.Psagenticcommerce.Admin'),
                'input' => [
                    $this->text(OpenAiFeedConfigResolver::SELLER_NAME, $module->translateAdmin('Seller name', [], 'Modules.Psagenticcommerce.Admin'), true),
                    $this->text(OpenAiFeedConfigResolver::SELLER_URL, $module->translateAdmin('Seller URL', [], 'Modules.Psagenticcommerce.Admin'), true),
                    $this->text(OpenAiFeedConfigResolver::RETURN_POLICY_URL, $module->translateAdmin('Return policy URL', [], 'Modules.Psagenticcommerce.Admin'), true),
                    $this->text(OpenAiFeedConfigResolver::TARGET_COUNTRIES, $module->translateAdmin('Target countries', [], 'Modules.Psagenticcommerce.Admin'), true, 'TR,DE,US'),
                    $this->text(OpenAiFeedConfigResolver::STORE_COUNTRY, $module->translateAdmin('Store country', [], 'Modules.Psagenticcommerce.Admin'), true, 'TR'),
                    $this->text(OpenAiFeedConfigResolver::DEFAULT_BRAND, $module->translateAdmin('Default brand', [], 'Modules.Psagenticcommerce.Admin'), false),
                    $this->switchField($module, OpenAiFeedConfigResolver::ELIGIBLE_SEARCH, $module->translateAdmin('Eligible for search', [], 'Modules.Psagenticcommerce.Admin')),
                    $this->switchField($module, OpenAiFeedConfigResolver::ELIGIBLE_CHECKOUT, $module->translateAdmin('Eligible for checkout', [], 'Modules.Psagenticcommerce.Admin')),
                    $this->text(OpenAiFeedConfigResolver::PRIVACY_POLICY_URL, $module->translateAdmin('Privacy policy URL', [], 'Modules.Psagenticcommerce.Admin'), false),
                    $this->text(OpenAiFeedConfigResolver::TERMS_URL, $module->translateAdmin('Terms URL', [], 'Modules.Psagenticcommerce.Admin'), false),
                    [
                        'type' => 'select',
                        'label' => $module->translateAdmin('Ads eligibility', [], 'Modules.Psagenticcommerce.Admin'),
                        'name' => OpenAiFeedConfigResolver::ADS_ELIGIBLE,
                        'options' => [
                            'query' => [
                                ['id' => '', 'name' => $module->translateAdmin('Not specified', [], 'Modules.Psagenticcommerce.Admin')],
                                ['id' => '1', 'name' => $module->translateAdmin('Eligible', [], 'Modules.Psagenticcommerce.Admin')],
                                ['id' => '0', 'name' => $module->translateAdmin('Not eligible', [], 'Modules.Psagenticcommerce.Admin')],
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
                'legend' => ['title' => $module->translateAdmin('OpenAI SFTP Delivery', [], 'Modules.Psagenticcommerce.Admin'), 'icon' => 'icon-cloud-upload'],
                'description' => $module->translateAdmin('Enter the SFTP values provided during merchant onboarding. Host-key verification is mandatory.', [], 'Modules.Psagenticcommerce.Admin'),
                'input' => [
                    $this->switchField($module, OpenAiSftpConfigResolver::ENABLED, $module->translateAdmin('Enable SFTP delivery', [], 'Modules.Psagenticcommerce.Admin')),
                    $this->text(OpenAiSftpConfigResolver::HOST, $module->translateAdmin('SFTP host', [], 'Modules.Psagenticcommerce.Admin'), false),
                    $this->text(OpenAiSftpConfigResolver::PORT, $module->translateAdmin('SFTP port', [], 'Modules.Psagenticcommerce.Admin'), false, '22'),
                    $this->text(OpenAiSftpConfigResolver::USERNAME, $module->translateAdmin('Username', [], 'Modules.Psagenticcommerce.Admin'), false),
                    [
                        'type' => 'select',
                        'label' => $module->translateAdmin('Authentication', [], 'Modules.Psagenticcommerce.Admin'),
                        'name' => OpenAiSftpConfigResolver::AUTH_MODE,
                        'options' => [
                            'query' => [
                                ['id' => 'secret', 'name' => $module->translateAdmin('Account secret', [], 'Modules.Psagenticcommerce.Admin')],
                                ['id' => 'public_key', 'name' => $module->translateAdmin('SSH public key', [], 'Modules.Psagenticcommerce.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'password',
                        'label' => $module->translateAdmin('Authentication secret', [], 'Modules.Psagenticcommerce.Admin'),
                        'name' => 'PSAGENTIC_OPENAI_SFTP_SECRET_INPUT',
                        'desc' => $module->translateAdmin('Leave blank to keep the currently stored encrypted value.', [], 'Modules.Psagenticcommerce.Admin'),
                    ],
                    $this->switchField($module, 'PSAGENTIC_OPENAI_SFTP_CLEAR_SECRET', $module->translateAdmin('Clear stored authentication secret', [], 'Modules.Psagenticcommerce.Admin')),
                    $this->text(OpenAiSftpConfigResolver::PRIVATE_KEY_FILE, $module->translateAdmin('Private key file path', [], 'Modules.Psagenticcommerce.Admin'), false),
                    $this->text(OpenAiSftpConfigResolver::PUBLIC_KEY_FILE, $module->translateAdmin('Public key file path', [], 'Modules.Psagenticcommerce.Admin'), false),
                    [
                        'type' => 'password',
                        'label' => $module->translateAdmin('Private-key passphrase', [], 'Modules.Psagenticcommerce.Admin'),
                        'name' => 'PSAGENTIC_OPENAI_SFTP_KEY_SECRET_INPUT',
                        'desc' => $module->translateAdmin('Leave blank to keep the currently stored encrypted value.', [], 'Modules.Psagenticcommerce.Admin'),
                    ],
                    $this->switchField($module, 'PSAGENTIC_OPENAI_SFTP_CLEAR_KEY_SECRET', $module->translateAdmin('Clear stored key passphrase', [], 'Modules.Psagenticcommerce.Admin')),
                    $this->text(OpenAiSftpConfigResolver::REMOTE_PATH, $module->translateAdmin('Remote file path', [], 'Modules.Psagenticcommerce.Admin'), false, 'products.jsonl.gz'),
                    $this->text(OpenAiSftpConfigResolver::HOST_KEY_SHA256, $module->translateAdmin('SSH host key SHA256', [], 'Modules.Psagenticcommerce.Admin'), false),
                    $this->text(OpenAiSftpConfigResolver::HOST_KEY_MD5, $module->translateAdmin('SSH host key MD5 fallback', [], 'Modules.Psagenticcommerce.Admin'), false),
                    $this->text(OpenAiSftpConfigResolver::KNOWN_HOSTS_FILE, $module->translateAdmin('known_hosts file path', [], 'Modules.Psagenticcommerce.Admin'), false),
                    $this->text(OpenAiSftpConfigResolver::TIMEOUT, $module->translateAdmin('Transfer timeout (seconds)', [], 'Modules.Psagenticcommerce.Admin'), false, '60'),
                    [
                        'type' => 'text',
                        'label' => $module->translateAdmin('Cron token', [], 'Modules.Psagenticcommerce.Admin'),
                        'name' => OpenAiConfigInstaller::CRON_TOKEN,
                        'readonly' => true,
                        'desc' => $module->translateAdmin('Use this only in the X-Agentic-Cron-Token HTTP header. Do not put it in the cron URL.', [], 'Modules.Psagenticcommerce.Admin'),
                    ],
                ],
                'submit' => [
                    'title' => $module->translateAdmin('Save settings', [], 'Modules.Psagenticcommerce.Admin'),
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
    private function switchField(\Module $module, string $name, string $label): array
    {
        return [
            'type' => 'switch',
            'label' => $label,
            'name' => $name,
            'is_bool' => true,
            'values' => [
                ['id' => $name . '_on', 'value' => 1, 'label' => $module->translateAdmin('Yes', [], 'Modules.Psagenticcommerce.Admin')],
                ['id' => $name . '_off', 'value' => 0, 'label' => $module->translateAdmin('No', [], 'Modules.Psagenticcommerce.Admin')],
            ],
        ];
    }
}
