<?php

namespace App\Domain\Sso\Services;

use App\Models\Tenant\SamlIdentityProvider;

class SamlServiceProvider
{
    /**
     * Generate the SP metadata XML.
     */
    public function getMetadata(SamlIdentityProvider $idp): string
    {
        $entityId = url("/saml/metadata/{$idp->id}");
        $acsUrl = url('/saml/acs');
        $sloUrl = url('/saml/sls');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<EntityDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" entityID="{$entityId}">
    <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</NameIDFormat>
        <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="{$acsUrl}" index="0" isDefault="true"/>
        <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="{$sloUrl}"/>
    </SPSSODescriptor>
</EntityDescriptor>
XML;
    }

    /**
     * Initiate SAML login — build AuthnRequest and return the redirect URL.
     */
    public function initiateLogin(SamlIdentityProvider $idp, string $requestId, ?string $relayState = null): string
    {
        $acsUrl = url('/saml/acs');
        $spEntityId = url("/saml/metadata/{$idp->id}");

        $authnRequest = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ' .
            'xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ' .
            'ID="' . htmlspecialchars($requestId) . '" ' .
            'Version="2.0" ' .
            'IssueInstant="' . now()->toIso8601String() . '" ' .
            'Destination="' . htmlspecialchars($idp->sso_url) . '" ' .
            'AssertionConsumerServiceURL="' . htmlspecialchars($acsUrl) . '">' .
            '<saml:Issuer>' . htmlspecialchars($spEntityId) . '</saml:Issuer>' .
            '</samlp:AuthnRequest>';

        $compressed = $this->deflateRaw($authnRequest);
        $encodedRequest = base64_encode($compressed);
        $params = ['SAMLRequest' => $encodedRequest];
        if ($relayState) $params['RelayState'] = $relayState;

        return $idp->sso_url . '?' . http_build_query($params);
    }

    /**
     * Process the SAML response and extract user attributes.
     */
    public function processResponse(SamlIdentityProvider $idp, string $xml): array
    {
        $attributes = [];

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $nameIdNodes = $dom->getElementsByTagName('NameID');
        if ($nameIdNodes->length > 0) {
            $attributes['email'] = $nameIdNodes->item(0)->textContent;
        }

        $attributeNodes = $dom->getElementsByTagName('Attribute');
        foreach ($attributeNodes as $attributeNode) {
            $name = $attributeNode->getAttribute('Name');
            $values = [];
            foreach ($attributeNode->childNodes as $child) {
                if ($child instanceof \DOMElement && $child->tagName === 'AttributeValue') {
                    $values[] = $child->textContent;
                }
            }
            $attributes[$name] = count($values) === 1 ? $values[0] : $values;
        }

        // Map IdP attributes to CMS attributes
        $mapped = [];
        foreach ($idp->attribute_mapping ?? [] as $cmsField => $idpAttribute) {
            $mapped[$cmsField] = $attributes[$idpAttribute] ?? null;
        }

        return array_merge($attributes, $mapped);
    }

    /**
     * Map IdP groups to CMS roles.
     */
    public function mapRoles($user, SamlIdentityProvider $idp, array $attributes): void
    {
        $roleMapping = $idp->role_mapping ?? [];
        if (empty($roleMapping)) return;

        $groupsAttribute = $roleMapping['groups_attribute'] ?? 'groups';
        $userGroups = $attributes[$groupsAttribute] ?? [];
        if (! is_array($userGroups)) $userGroups = [$userGroups];

        $mappings = $roleMapping['mappings'] ?? [];
        $assignedRoles = [];

        foreach ($mappings as $mapping) {
            if (is_array($mapping) && in_array($mapping['idp_group'] ?? '', $userGroups)) {
                $assignedRoles[] = $mapping['cms_role'] ?? 'editor';
            }
        }

        if (! empty($assignedRoles)) {
            $strict = config('sso.sync_roles_strict', false);
            if ($strict && method_exists($user, 'roles')) {
                $user->roles()->sync([]);
            }
            foreach ($assignedRoles as $roleName) {
                if (method_exists($user, 'assignRole')) {
                    $user->assignRole($roleName);
                }
            }
        }
    }

    protected function deflateRaw(string $data): string
    {
        $context = deflate_init(ZLIB_ENCODING_RAW);
        return deflate_add($context, $data, ZLIB_FINISH);
    }
}
