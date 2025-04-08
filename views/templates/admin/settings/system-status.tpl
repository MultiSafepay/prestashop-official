{**
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
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 *}

<div id="multisafepay-system-status">
    <p>{l s='Please copy and paste this information in your ticket when contacting support.' mod='multisafepayofficial'}</p>
    <p class="submit">
        <a href="#" onclick="jQuery( '#multisafepay-system-status-report' ).toggle( 'slow');" class="btn btn-default pull-right multisafepay-system-status-report">{l s='Get system report'  mod='multisafepayofficial' }</a>
    </p>
    <div id="multisafepay-system-status-report">
        <textarea readonly="readonly" style="min-height: 300px;">{$plain_status_report}</textarea>
    </div>
    {foreach from=$status_report item=status_report_section}
        <div class="table-responsive">
            <table class="multisafepay_status_table table">
                <thead>
                    <tr>
                        <th colspan="2">
                            <h2>
                                {$status_report_section['title']|escape:'html':'UTF-8'}
                            </h2>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {if isset($status_report_section['settings'])}
                        {foreach from=$status_report_section['settings'] item=setting}
                            <tr>
                                <td>
                                    {$setting['label']|escape:'html':'UTF-8'}
                                </td>
                                <td>
                                    {$setting['value']|escape:'html':'UTF-8'}
                                </td>
                            </tr>
                        {/foreach}
                    {/if}
                </tbody>
            </table>
        </div>
    {/foreach}
</div>
