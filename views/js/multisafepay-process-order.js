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
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */


$(document).ready(function () {
    if (typeof prestashop !== 'undefined') {
        processMultiSafepayPayment(0);
    }
});

function processMultiSafepayPayment(attempt)
{
    $.ajax({
        async: true,
        dataType: 'json',
        url: window['orderExistsEndpoint'],
        success: function (data) {
            if (data) {
                window.location.href = data.redirectUrl;
                return;
            } else {
                attempt++;
                if (attempt > 5) {
                    window.location.href = window['orderHistoryUrl'];
                    return;
                }
            }
        },
        error: function (request, status, error) {
            attempt++;
            if (attempt > 5) {
                window.location.href = window['orderHistoryUrl'];
                return;
            }
            setTimeout(processMultiSafepayPayment, 2500, attempt);
        }
    });
}
