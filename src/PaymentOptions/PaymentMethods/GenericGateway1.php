<?php
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use Configuration;
use HelperUploader;
use Context;
use Media;
use Validate;

class GenericGateway1 extends BasePaymentOption
{
    public const CLASS_NAME = 'GenericGateway1';

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->module->l('Generic Gateway 1', self::CLASS_NAME);
    }

    public function getGatewayCode(): string
    {
        return (Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_CODE_'.$this->getUniqueName()) ?: '');
    }

    public function getLogo(): string
    {
        return Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_' . $this->getUniqueName()) ?? '';
    }

    public function getUniqueName(): string
    {
        return 'GENERIC1';
    }

    /**
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function getGatewaySettings(): array
    {
        $options = parent::getGatewaySettings();

        $options['MULTISAFEPAY_OFFICIAL_GATEWAY_CODE_'.$this->getUniqueName()] = [
            'type' => 'text',
            'name' => $this->module->l('Gateway code', self::CLASS_NAME),
            'value' => Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_CODE_'.$this->getUniqueName()),
            'default' => '',
            'order' => 31,
        ];
        $options['MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_'.$this->getUniqueName()] = [
            'type'           => 'file',
            'image'          => Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_' . $this->getUniqueName()) ?? '',
            'name'           => $this->module->l('Gateway icon', self::CLASS_NAME),
            'default'        => '',
            'order'          => 32,
            'helperText'     => $this->module->l('Recommended size: 420px * 180px. Recommended format: .png', self::CLASS_NAME),
        ];


        $isBackoffice = Validate::isLoadedObject(Context::getContext()->employee);
        if ($isBackoffice) {
            $options['MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_'.$this->getUniqueName()]['render'] = $this->getRenderedUploaderField();
        }

        return $this->sortInputFields($options);
    }

    public function getRenderedUploaderField(): string
    {
        $uploader = new HelperUploader();
        $uploader->setContext(Context::getContext());
        $uploader->setId($this->getUniqueName());
        $uploader->setName('MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_'.$this->getUniqueName());
        $uploader->setUseAjax(false);
        if (!empty(Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_' . $this->getUniqueName()))) {
            $uploader->setFiles(
                [
                    0 => [
                        'type' => HelperUploader::TYPE_IMAGE,
                        'image' => Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_' . $this->getUniqueName()) ? '<img src="' . Media::getMediaPath(Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_' . $this->getUniqueName())) . '" width="80" />' :  null,
                        'size' => null,
                        'delete_url' =>  '#',
                        'name' => Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_' . $this->getUniqueName()) ? '<img src="' . Media::getMediaPath(Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_' . $this->getUniqueName())) . '" width="80" />' :  null,
                        'title' => Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_' . $this->getUniqueName()) ? '<img src="' . Media::getMediaPath(Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_' . $this->getUniqueName())) . '" width="80" />' :  null,
                    ],
                ]
            );
        }
        return $uploader->render();
    }
}
