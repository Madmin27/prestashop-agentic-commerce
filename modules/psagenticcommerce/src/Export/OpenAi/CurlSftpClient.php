<?php

namespace PrestaShopAgenticCommerce\Export\OpenAi;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CurlSftpClient
{
    /** @return array{bytes:int,remote_url:string} */
    public function upload(string $localPath, OpenAiSftpConfig $config): array
    {
        $config->assertReady();
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('PHP cURL extension is required for OpenAI SFTP delivery.');
        }
        $protocols = array_map('strtolower', curl_version()['protocols'] ?? []);
        if (!in_array('sftp', $protocols, true)) {
            throw new \RuntimeException('This libcurl build does not support SFTP.');
        }
        if (!is_file($localPath) || !is_readable($localPath)) {
            throw new \RuntimeException('OpenAI snapshot file is not readable.');
        }

        $host = trim((string) $config->get('host', ''));
        $port = (int) $config->get('port', 22);
        $remotePath = '/' . ltrim((string) $config->get('remote_path', 'products.jsonl.gz'), '/');
        $remoteUrl = 'sftp://' . $host . ':' . $port . $remotePath;
        $handle = fopen($localPath, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open OpenAI snapshot file.');
        }

        $curl = curl_init();
        if ($curl === false) {
            fclose($handle);
            throw new \RuntimeException('Unable to initialize cURL.');
        }

        try {
            $options = [
                CURLOPT_URL => $remoteUrl,
                CURLOPT_UPLOAD => true,
                CURLOPT_INFILE => $handle,
                CURLOPT_INFILESIZE => filesize($localPath),
                CURLOPT_USERNAME => (string) $config->get('username', ''),
                CURLOPT_CONNECTTIMEOUT => min(30, (int) $config->get('timeout', 60)),
                CURLOPT_TIMEOUT => (int) $config->get('timeout', 60),
                CURLOPT_RETURNTRANSFER => true,
            ];

            if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_SFTP')) {
                $options[CURLOPT_PROTOCOLS] = CURLPROTO_SFTP;
            }

            if ((string) $config->get('auth_mode', 'secret') === 'public_key') {
                if (!defined('CURLSSH_AUTH_PUBLICKEY')) {
                    throw new \RuntimeException('This cURL build does not expose public-key SSH authentication.');
                }
                $options[CURLOPT_SSH_AUTH_TYPES] = CURLSSH_AUTH_PUBLICKEY;
                $options[CURLOPT_SSH_PRIVATE_KEYFILE] = (string) $config->get('private_key_file', '');
                $publicKeyFile = trim((string) $config->get('public_key_file', ''));
                if ($publicKeyFile !== '') {
                    $options[CURLOPT_SSH_PUBLIC_KEYFILE] = $publicKeyFile;
                }
                $keyPassphrase = (string) $config->get('key_passphrase', '');
                if ($keyPassphrase !== '') {
                    $options[CURLOPT_KEYPASSWD] = $keyPassphrase;
                }
            } else {
                if (defined('CURLSSH_AUTH_PASSWORD')) {
                    $options[CURLOPT_SSH_AUTH_TYPES] = CURLSSH_AUTH_PASSWORD;
                }
                $options[CURLOPT_PASSWORD] = (string) $config->get('auth_secret', '');
            }

            $sha256 = trim((string) $config->get('host_key_sha256', ''));
            $md5 = strtolower(trim((string) $config->get('host_key_md5', '')));
            $knownHosts = trim((string) $config->get('known_hosts_file', ''));
            if ($sha256 !== '') {
                if (!defined('CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256')) {
                    throw new \RuntimeException('SHA256 SSH host-key pinning requires PHP/cURL support for CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256.');
                }
                $options[CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256] = $sha256;
            } elseif ($md5 !== '') {
                if (!defined('CURLOPT_SSH_HOST_PUBLIC_KEY_MD5')) {
                    throw new \RuntimeException('MD5 SSH host-key pinning is unavailable in this cURL build.');
                }
                $options[CURLOPT_SSH_HOST_PUBLIC_KEY_MD5] = preg_replace('/[^0-9a-f]/', '', $md5);
            } elseif ($knownHosts !== '') {
                if (!defined('CURLOPT_SSH_KNOWNHOSTS')) {
                    throw new \RuntimeException('known_hosts verification is unavailable in this cURL build.');
                }
                $options[CURLOPT_SSH_KNOWNHOSTS] = $knownHosts;
            }

            curl_setopt_array($curl, $options);
            $result = curl_exec($curl);
            if ($result === false) {
                throw new \RuntimeException('OpenAI SFTP upload failed: ' . curl_error($curl));
            }

            return [
                'bytes' => (int) filesize($localPath),
                'remote_url' => $remoteUrl,
            ];
        } finally {
            curl_close($curl);
            fclose($handle);
        }
    }
}
