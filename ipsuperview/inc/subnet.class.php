<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginIpsuperviewSubnet extends CommonDBTM
{
    public static $rightname = 'plugin_ipsuperview';

    public $dohistory = true;

    public static function getTypeName($nb = 0)
    {
        return plugin_ipsuperview_t('IP网段', 'IP Subnet');
    }

    public static function getIcon()
    {
        return 'ti ti-chart-grid-dots';
    }

    public function rawSearchOptions()
    {
        return [
            [
                'id'   => 'common',
                'name' => self::getTypeName(2),
            ],
            [
                'id'       => '1',
                'table'    => $this->getTable(),
                'field'    => 'name',
                'name'     => __('Name'),
                'datatype' => 'itemlink',
            ],
            [
                'id'    => '2',
                'table' => $this->getTable(),
                'field' => 'cidr',
                'name'  => __('CIDR', 'ipsuperview'),
            ],
            [
                'id'       => '3',
                'table'    => $this->getTable(),
                'field'    => 'first_ip',
                'name'     => __('First IP', 'ipsuperview'),
                'datatype' => 'ip',
            ],
            [
                'id'       => '4',
                'table'    => $this->getTable(),
                'field'    => 'last_ip',
                'name'     => __('Last IP', 'ipsuperview'),
                'datatype' => 'ip',
            ],
            [
                'id'       => '5',
                'table'    => $this->getTable(),
                'field'    => 'host_count',
                'name'     => __('Hosts', 'ipsuperview'),
                'datatype' => 'number',
            ],
            [
                'id'       => '80',
                'table'    => 'glpi_entities',
                'field'    => 'completename',
                'name'     => __('Entity'),
                'datatype' => 'dropdown',
            ],
            [
                'id'       => '30',
                'table'    => $this->getTable(),
                'field'    => 'comment',
                'name'     => __('Comments'),
                'datatype' => 'text',
            ],
        ];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    public function post_getEmpty()
    {
        $this->fields['entities_id'] = $_SESSION['glpiactive_entity'];
    }

    public function prepareInputForAdd($input)
    {
        return $this->normalizeInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->normalizeInput($input);
    }

    private function t($zh, $en = null, array $params = [])
    {
        return plugin_ipsuperview_t($zh, $en, $params);
    }

    private function normalizeInput(array $input)
    {
        if (!isset($input['name']) || trim($input['name']) === '') {
            Session::addMessageAfterRedirect(__('A name is required.', 'ipsuperview'), false, ERROR);
            return false;
        }

        if (!isset($input['cidr'])) {
            Session::addMessageAfterRedirect(__('A CIDR is required.', 'ipsuperview'), false, ERROR);
            return false;
        }

        $range = self::parseCidr($input['cidr']);
        if ($range === false) {
            Session::addMessageAfterRedirect(
                __('CIDR must be IPv4 and between /20 and /30.', 'ipsuperview'),
                false,
                ERROR
            );
            return false;
        }

        $input['name'] = trim($input['name']);
        $input['cidr'] = $range['cidr'];
        $input['first_ip'] = $range['first_ip'];
        $input['last_ip'] = $range['last_ip'];
        $input['host_count'] = $range['host_count'];
        $input['comment'] = isset($input['comment']) ? trim($input['comment']) : '';
        $input['entities_id'] = isset($input['entities_id']) ? (int) $input['entities_id'] : $_SESSION['glpiactive_entity'];

        return $input;
    }

    public function showForm($ID, $options = [])
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);
        $preview = self::parseCidr($this->fields['cidr'] ?? '');

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Name') . "</td>";
        echo "<td>";
        echo Html::input('name', ['value' => $this->fields['name'] ?? '', 'required' => true]);
        echo "</td>";
        echo "<td>" . __('Entity') . "</td>";
        echo "<td>";
        Entity::dropdown(['value' => $this->fields['entities_id'] ?? $_SESSION['glpiactive_entity']]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('CIDR', 'ipsuperview') . "</td>";
        echo "<td colspan='3'>";
        echo Html::input('cidr', [
            'value'       => $this->fields['cidr'] ?? '',
            'required'    => true,
            'placeholder' => '192.168.32.0/24',
        ]);
        echo "<div class='ipsuperview-formnote'>" . $this->t(
            '支持的网段范围是 /20 到 /30；如果是 /24 或更小的网段，详情页会显示热力图。',
            'Supported subnet size is /20 to /30. Heatmaps are shown for /24 or smaller ranges.'
        ) . "</div>";
        echo "<div class='ipsuperview-cidrpreview'>";
        echo "<div class='ipsuperview-cidrpreview__status ipsuperview-formhint ipsuperview-formhint--muted' data-ipsuperview-preview-status>";
        echo $preview !== false
            ? $this->t('已识别这个网段，下面是自动计算出的可用范围。', 'Subnet recognized. The usable range below was calculated automatically.')
            : $this->t('输入网段后，这里会自动显示标准化结果、可用范围和主机数。', 'After you enter a subnet, the normalized CIDR, usable range, and host count will appear here.');
        echo "</div>";
        echo "<div class='ipsuperview-cidrpreview__grid'>";
        echo "<div class='ipsuperview-cidrpreview__item'>";
        echo "<span class='ipsuperview-cidrpreview__label'>" . $this->t('标准化网段', 'Normalized CIDR') . "</span>";
        echo "<strong class='ipsuperview-cidrpreview__value' data-ipsuperview-preview-cidr>" . $this->escape($preview['cidr'] ?? $this->t('待输入', 'Waiting')) . "</strong>";
        echo "</div>";
        echo "<div class='ipsuperview-cidrpreview__item'>";
        echo "<span class='ipsuperview-cidrpreview__label'>" . $this->t('起始 IP', 'First usable IP') . "</span>";
        echo "<strong class='ipsuperview-cidrpreview__value' data-ipsuperview-preview-first>" . $this->escape($preview['first_ip'] ?? $this->t('待输入', 'Waiting')) . "</strong>";
        echo "</div>";
        echo "<div class='ipsuperview-cidrpreview__item'>";
        echo "<span class='ipsuperview-cidrpreview__label'>" . $this->t('结束 IP', 'Last usable IP') . "</span>";
        echo "<strong class='ipsuperview-cidrpreview__value' data-ipsuperview-preview-last>" . $this->escape($preview['last_ip'] ?? $this->t('待输入', 'Waiting')) . "</strong>";
        echo "</div>";
        echo "<div class='ipsuperview-cidrpreview__item'>";
        echo "<span class='ipsuperview-cidrpreview__label'>" . $this->t('可用主机数', 'Usable hosts') . "</span>";
        echo "<strong class='ipsuperview-cidrpreview__value' data-ipsuperview-preview-hosts>" . (int) ($preview['host_count'] ?? 0) . "</strong>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Comments') . "</td>";
        echo "<td colspan='3'>";
        echo Html::textarea([
            'name'  => 'comment',
            'value' => $this->fields['comment'] ?? '',
            'cols'  => 80,
        ]);
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons($options);

        return true;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() === __CLASS__) {
            $item->showOverview();
        }

        return true;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return [$this->t('总览', 'Overview')];
    }

    public function showOverview()
    {
        if (!$this->getID()) {
            return;
        }

        $overview = $this->computeOverview();
        $summary = $overview['summary'];
        $usage = $summary['host_count'] > 0 ? round(($summary['assigned'] / $summary['host_count']) * 100, 1) : 0;
        $assignedEntries = $overview['assigned_entries'];

        echo "<div class='ipsuperview-overview'>";
        echo "<div class='ipsuperview-summarybox'>";
        echo "<div class='ipsuperview-summarybox__title'>" . $this->t('一眼看懂这个网段', 'Read this subnet at a glance') . "</div>";
        echo "<div class='ipsuperview-summarybox__text'>" . $this->t(
            '这个网段共有 <strong>%d</strong> 个可用 IP，已占用 <strong>%d</strong> 个，空闲 <strong>%d</strong> 个，冲突 <strong>%d</strong> 个，保留/例外地址 <strong>%d</strong> 个，实扫在线但未登记到 GLPI 的有 <strong>%d</strong> 个；其中 GLPI 资料超过 14 天未更新的固定 IP 有 <strong>%d</strong> 个。',
            'This subnet has <strong>%d</strong> usable IPs. <strong>%d</strong> are occupied, <strong>%d</strong> are free, <strong>%d</strong> are conflicts, <strong>%d</strong> are reserved or exceptions, and <strong>%d</strong> are live but not registered in GLPI. <strong>%d</strong> fixed-IP records have not been updated in GLPI for more than 14 days.',
            [(int) $summary['host_count'], (int) $summary['assigned'], (int) $summary['free'], (int) $summary['duplicates'], (int) $summary['reserved'], (int) $summary['discovered_only'], (int) $summary['stale']]
        ) . "</div>";
        echo "<div class='ipsuperview-summarybox__text'>" . $this->t(
            '固定 IP 对应表现在会并排显示 <strong>GLPI 登记名称</strong> 和 <strong>当前发现名称</strong>。现场扫描优先告诉你设备现在是否还在，GLPI 名称则用于判断这条历史登记是不是已经陈旧。',
            'The fixed IP table now shows the <strong>GLPI registered name</strong> and the <strong>currently discovered name</strong> side by side. Live scan data tells you whether the device is still present; the GLPI name helps you judge whether the historical record is stale.'
        ) . "</div>";
        echo "</div>";

        echo $this->renderScanToolbar($overview['scan_meta']);

        echo "<div class='ipsuperview-cards'>";
        echo $this->renderCard($this->t('网段', 'Subnet'), $this->escape($this->fields['cidr']), 'neutral');
        echo $this->renderCard($this->t('已占用', 'Occupied'), (string) $summary['assigned'], 'assigned');
        echo $this->renderCard($this->t('冲突', 'Conflicts'), (string) $summary['duplicates'], 'duplicate');
        echo $this->renderCard($this->t('空闲', 'Free'), (string) $summary['free'], 'free');
        echo $this->renderCard($this->t('保留/例外', 'Reserved / Exceptions'), (string) $summary['reserved'], 'neutral');
        echo $this->renderCard($this->t('在线未登记', 'Live Unregistered'), (string) $summary['discovered_only'], 'neutral');
        echo $this->renderCard($this->t('资料陈旧', 'Stale Records'), (string) $summary['stale'], 'stale');
        echo $this->renderCard($this->t('使用率', 'Usage'), $usage . '%', 'neutral');
        echo "</div>";

        echo "<div class='ipsuperview-meta'>";
        echo "<span><strong>" . $this->t('地址范围:', 'Address range:') . "</strong> " .
            $this->escape($this->fields['first_ip']) . " - " . $this->escape($this->fields['last_ip']) . "</span>";
        echo "<span><strong>" . $this->t('可用地址数:', 'Usable addresses:') . "</strong> " . (int) $this->fields['host_count'] . "</span>";
        echo "</div>";

        echo "<div class='ipsuperview-legend'>";
        echo "<span class='ipsuperview-legend__item'><span class='ipsuperview-legend__dot ipsuperview-legend__dot--assigned'></span>" .
            $this->t('已占用', 'Occupied') . "</span>";
        echo "<span class='ipsuperview-legend__item'><span class='ipsuperview-legend__dot ipsuperview-legend__dot--duplicate'></span>" .
            $this->t('冲突', 'Conflict') . "</span>";
        echo "<span class='ipsuperview-legend__item'><span class='ipsuperview-legend__dot ipsuperview-legend__dot--free'></span>" .
            $this->t('空闲', 'Free') . "</span>";
        echo "</div>";

        echo $this->renderWorkspaceNav($summary);

        if ($summary['host_count'] <= 256) {
            echo "<section class='ipsuperview-panel' data-ipsuperview-workspace-panel='heatmap'>";
            echo "<div class='ipsuperview-panel__head'><h3>" . $this->t('地址热力图', 'IP Heatmap') . "</h3></div>";
            echo "<div class='ipsuperview-sectionhint'>" . $this->t('点击任意数字，可以跳到下面对应 IP 的说明。', 'Click any number to jump to the matching IP details below.') . "</div>";
            echo $this->renderHeatmap($overview['ips']);
            echo "</section>";
        } else {
            echo "<section class='ipsuperview-panel' data-ipsuperview-workspace-panel='heatmap'>";
            echo "<div class='alert alert-info'>";
            echo $this->t('热力图只在 /24 或更小的网段中显示，更大的网段只展示汇总和异常信息。', 'Heatmaps are shown only for /24 or smaller ranges. Larger ranges display summary and exceptions only.');
            echo "</div>";
            echo "</section>";
        }

        echo "<section class='ipsuperview-panel' data-ipsuperview-workspace-panel='duplicates'>";
        echo "<div class='ipsuperview-panel__head'><h3>" . $this->t('异常地址', 'Conflict & Exception IPs') . "</h3></div>";
        echo $this->renderDuplicatesTable($overview['duplicates']);
        echo "</section>";

        echo "<section class='ipsuperview-panel' data-ipsuperview-workspace-panel='assigned'>";
        echo "<div class='ipsuperview-panel__head'><h3>" . $this->t('固定IP设备对应表', 'Fixed IP Device Mapping') . "</h3></div>";
        echo "<div class='ipsuperview-sectionhint'>" . $this->t('这张表会同时显示 GLPI 里的历史登记名称、设备备注、这次扫描看到的当前名称，以及 GLPI 资料最后更新时间。优先看“资料状态”“备注”和“当前发现名称”，这样能更快识别陈旧资产记录。', 'This table shows the GLPI registered name, asset comment, currently discovered name, and the last GLPI update time together. Prioritize freshness, comments, and the currently discovered name to spot stale records faster.') . "</div>";
        echo $this->renderAssignedLookup($assignedEntries, $overview['scan_index']);
        echo "</section>";

        echo "<section class='ipsuperview-panel' data-ipsuperview-workspace-panel='discovered'>";
        echo "<div class='ipsuperview-panel__head'><h3>" . $this->t('实扫发现但未登记到GLPI', 'Live But Not Registered in GLPI') . "</h3></div>";
        echo "<div class='ipsuperview-sectionhint'>" . $this->t('这些 IP 在网段里实际在线，但当前没有在 GLPI 资产里找到对应设备。', 'These IPs are live in the subnet, but no matching GLPI asset was found.') . "</div>";
        echo $this->renderDiscoveredDevices($overview['discovered_only']);
        echo "</section>";

        echo "<section class='ipsuperview-panel' data-ipsuperview-workspace-panel='labels'>";
        echo "<div class='ipsuperview-panel__head'><h3>" . $this->t('人工别名和保留地址', 'Manual Aliases & Reserved IPs') . "</h3></div>";
        echo "<div class='ipsuperview-sectionhint'>" . $this->t('给未登记设备补一个你看得懂的名称，或者把例外地址标记成保留/基础设施地址。保存后总览和导出报表都会直接使用这些名字。', 'Add a readable alias for unregistered devices, or mark exception addresses as reserved or infrastructure. Saved names are used immediately in the overview and exports.') . "</div>";
        echo $this->renderLabelManager();
        echo $this->renderIpLabelsTable($overview['labels'], $overview['scan_index']);
        echo "</section>";

        if ($summary['host_count'] <= 512) {
            echo "<section class='ipsuperview-panel ipsuperview-panel--details' data-ipsuperview-workspace-panel='details'>";
            echo "<div class='ipsuperview-quickjump'>";
            echo "<button type='button' class='ipsuperview-quickfilters__btn' data-ipsuperview-open-details='details-table' data-ipsuperview-open-mode='free'>" . $this->t('一键查看未使用 IP（%d 个）', 'Show unused IPs (%d)', [(int) $summary['free']]) . "</button>";
            echo "<button type='button' class='ipsuperview-quickfilters__btn' data-ipsuperview-open-details='details-table' data-ipsuperview-open-mode='all'>" . $this->t('查看全部 IP 明细', 'Show all IP details') . "</button>";
            echo "</div>";
            echo "<details class='ipsuperview-details' data-ipsuperview-details='details-table'>";
            echo "<summary>" . $this->t('查看全部 IP 明细（含空闲地址，%d 条）', 'All IP details (%d rows, including free IPs)', [count($overview['ips'])]) . "</summary>";
            echo "<div class='ipsuperview-sectionhint'>" . $this->t('只有在排查某个空闲 IP 或逐个核对地址时，才需要展开这部分。', 'Open this section only when you need to inspect a specific free IP or verify addresses one by one.') . "</div>";
            echo $this->renderDetailsTable($overview['ips']);
            echo "</details>";
            echo "</section>";
        }

        echo '</div>';
    }

    private function renderWorkspaceNav(array $summary)
    {
        $items = [
            ['key' => 'assigned', 'label' => $this->t('固定 IP', 'Fixed IP'), 'meta' => (string) $summary['assigned'], 'active' => true],
            ['key' => 'duplicates', 'label' => $this->t('冲突', 'Conflicts'), 'meta' => (string) $summary['duplicates']],
            ['key' => 'discovered', 'label' => $this->t('未登记', 'Unregistered'), 'meta' => (string) $summary['discovered_only']],
            ['key' => 'details', 'label' => $this->t('未使用 IP', 'Unused IPs'), 'meta' => (string) $summary['free']],
            ['key' => 'labels', 'label' => $this->t('人工标记', 'Manual Labels'), 'meta' => (string) $summary['reserved']],
            ['key' => 'heatmap', 'label' => $this->t('热力图', 'Heatmap'), 'meta' => $summary['host_count'] <= 256 ? $this->t('可用', 'Available') : $this->t('仅小网段', 'Small ranges only')],
            ['key' => 'all', 'label' => $this->t('全部展开', 'Expand All'), 'meta' => $this->t('完整页', 'Full page')],
        ];

        $html = "<div class='ipsuperview-workspace'>";
        $html .= "<div class='ipsuperview-workspace__intro'>";
        $html .= "<div class='ipsuperview-workspace__eyebrow'>" . $this->t('工作区导航', 'Workspace Navigation') . "</div>";
        $html .= "<div class='ipsuperview-workspace__title'>" . $this->t('别再整页往下翻，直接切到你要处理的那一块。', 'Stop scrolling the whole page. Jump straight to the workspace you need.') . "</div>";
        $html .= "</div>";
        $html .= "<div class='ipsuperview-workspace__nav' data-ipsuperview-workspace='overview'>";

        foreach ($items as $item) {
            $activeClass = !empty($item['active']) ? ' is-active' : '';
            $pressed = !empty($item['active']) ? 'true' : 'false';
            $html .= "<button type='button' class='ipsuperview-workspace__tab" . $activeClass . "' data-ipsuperview-workspace-target='" . $this->escape($item['key']) . "' aria-pressed='" . $pressed . "'>";
            $html .= "<span class='ipsuperview-workspace__tablabel'>" . $this->escape($item['label']) . "</span>";
            $html .= "<span class='ipsuperview-workspace__tabmeta'>" . $this->escape($item['meta']) . "</span>";
            $html .= "</button>";
        }

        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }

    private function renderCard($label, $value, $status)
    {
        return "<div class='ipsuperview-card ipsuperview-card--" . $status . "'>" .
            "<div class='ipsuperview-card__label'>" . $label . "</div>" .
            "<div class='ipsuperview-card__value'>" . $value . "</div>" .
            "</div>";
    }

    private function renderHeatmap(array $ips)
    {
        $html = "<div class='ipsuperview-grid'>";

        foreach ($ips as $entry) {
            $suffix = (int) substr(strrchr($entry['ip'], '.'), 1);
            $title = $this->escape($entry['ip'] . ' - ' . $this->getStatusLabel($entry['status']) . $this->buildTitleSuffix($entry));
            $html .= "<a class='ipsuperview-cell ipsuperview-cell--" . $entry['status'] . "' href='#ipsuperview-" .
                $this->escape($entry['ip']) . "' title='" . $title . "'>" . $suffix . '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    private function buildTitleSuffix(array $entry)
    {
        if ($entry['status'] === 'free') {
            return '';
        }

        $names = [];
        foreach ($entry['items'] as $item) {
            $names[] = $item['dname'];
        }

        return ' - ' . implode(', ', array_unique($names));
    }

    private function renderDuplicatesTable(array $duplicates)
    {
        if (count($duplicates) === 0) {
            return "<div class='alert alert-success'>" . $this->t('这个网段没有发现冲突 IP。', 'No duplicate IP conflicts were found in this subnet.') . "</div>";
        }

        $html = "<table class='tab_cadre_fixehov'>";
        $html .= "<tr><th>" . $this->t('冲突 IP', 'Conflicting IP') . "</th><th>" . $this->t('关联设备', 'Related devices') . "</th><th>" . $this->t('GLPI备注', 'GLPI comment') . "</th><th>" . $this->t('GLPI最后更新', 'Last GLPI update') . "</th><th>" . $this->t('资料状态', 'Record freshness') . "</th><th>" . $this->t('MAC 地址', 'MAC address') . "</th><th>" . $this->t('资产类型', 'Asset type') . "</th></tr>";

        foreach ($duplicates as $entry) {
            $names = [];
            $comments = [];
            $updatedAts = [];
            $freshnesses = [];
            $macs = [];
            $types = [];
            foreach ($entry['items'] as $item) {
                $freshness = $this->getAssetFreshnessMeta($item['asset_updated_at'] ?? '');
                $names[] = $this->renderAssetLink($item);
                $comments[] = $this->renderAssetComment((string) ($item['asset_comment'] ?? ''));
                $updatedAts[] = $this->renderAssetTimestamp($freshness);
                $freshnesses[] = "<span class='ipsuperview-badge ipsuperview-badge--freshness-" . $freshness['code'] . "'>" .
                    $this->escape($freshness['label']) . "</span>";
                $macs[] = $this->escape($item['mac']);
                $types[] = $this->escape($item['itemtype']);
            }

            $html .= "<tr>";
            $html .= "<td>" . $this->escape($entry['ip']) . "</td>";
            $html .= "<td>" . implode('<br>', $names) . "</td>";
            $html .= "<td>" . implode('<br>', $comments) . "</td>";
            $html .= "<td>" . implode('<br>', $updatedAts) . "</td>";
            $html .= "<td>" . implode('<br>', $freshnesses) . "</td>";
            $html .= "<td>" . implode('<br>', array_unique(array_filter($macs))) . "</td>";
            $html .= "<td>" . implode('<br>', array_unique($types)) . "</td>";
            $html .= "</tr>";
        }

        $html .= '</table>';

        return $html;
    }

    private function renderDetailsTable(array $ips)
    {
        $html = "<div class='ipsuperview-quickfilters'>";
        $html .= "<button type='button' class='ipsuperview-quickfilters__btn is-active' data-ipsuperview-quickfilter='details-table' data-ipsuperview-mode='all'>" . $this->t('显示全部 IP', 'Show all IPs') . "</button>";
        $html .= "<button type='button' class='ipsuperview-quickfilters__btn' data-ipsuperview-quickfilter='details-table' data-ipsuperview-mode='free'>" . $this->t('只看未使用 IP', 'Unused IPs only') . "</button>";
        $html .= "<button type='button' class='ipsuperview-quickfilters__btn' data-ipsuperview-quickfilter='details-table' data-ipsuperview-mode='used'>" . $this->t('只看已占用 / 冲突', 'Occupied / conflict only') . "</button>";
        $html .= "</div>";
        $html = $html . "<table class='tab_cadre_fixehov' data-ipsuperview-table='details-table'>";
        $html .= "<tr><th>IP</th><th>" . $this->t('状态', 'Status') . "</th><th>" . $this->t('关联设备', 'Related devices') . "</th><th>" . $this->t('MAC 地址', 'MAC address') . "</th><th>" . $this->t('资产类型', 'Asset type') . "</th></tr>";

        foreach ($ips as $entry) {
            $searchNames = [];
            $searchMacs = [];
            $searchTypes = [];
            foreach ($entry['items'] as $item) {
                $searchNames[] = $this->getAssetDisplayName($item);
                $searchMacs[] = (string) $item['mac'];
                $searchTypes[] = (string) $item['itemtype'];
            }
            $searchText = strtolower(trim(implode(' ', array_filter([
                $entry['ip'],
                $this->getStatusLabel($entry['status']),
                implode(' ', $searchNames),
                implode(' ', $searchMacs),
                implode(' ', $searchTypes),
            ]))));

            $html .= "<tr data-ipsuperview-row='" . $this->escape($searchText) . "' data-ipsuperview-status='" . $this->escape($entry['status']) . "' id='ipsuperview-" . $this->escape($entry['ip']) . "'>";
            $html .= "<td>" . $this->escape($entry['ip']) . "</td>";
            $html .= "<td><span class='ipsuperview-badge ipsuperview-badge--" . $entry['status'] . "'>" .
                $this->escape($this->getStatusLabel($entry['status'])) . "</span></td>";

            if ($entry['status'] === 'free') {
                $html .= "<td colspan='3' class='center'>" . $this->t('当前没有关联资产。', 'No related asset is linked to this IP.') . "</td>";
                $html .= '</tr>';
                continue;
            }

            $names = [];
            $macs = [];
            $types = [];
            foreach ($entry['items'] as $item) {
                $names[] = $this->renderAssetLink($item);
                $macs[] = $this->renderNetworkPortLink($item);
                $types[] = $this->escape($item['itemtype']);
            }

            $html .= "<td>" . implode('<br>', $names) . "</td>";
            $html .= "<td>" . implode('<br>', array_filter($macs)) . "</td>";
            $html .= "<td>" . implode('<br>', array_unique($types)) . "</td>";
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }

    private function renderAssignedLookup(array $ips, array $scanIndex, $tableName = 'assigned-table')
    {
        if (count($ips) === 0) {
            return "<div class='alert alert-info'>" . $this->t('这个网段还没有发现已绑定设备的固定 IP。', 'No fixed IP device assignment was found in this subnet yet.') . "</div>";
        }

        $html = "<div class='ipsuperview-filterbox'>";
        $html .= "<input type='search' class='form-control' placeholder='" . $this->escape($this->t('搜索 IP、设备名称或 MAC', 'Search IP, device name, or MAC')) . "' " .
            "data-ipsuperview-filter='" . $this->escape($tableName) . "' />";
        $html .= "</div>";
        $html .= "<div class='ipsuperview-quickfilters'>";
        $html .= "<button type='button' class='ipsuperview-quickfilters__btn is-active' data-ipsuperview-quickfilter='" . $this->escape($tableName) . "' data-ipsuperview-mode='all'>" . $this->t('显示全部固定 IP', 'Show all fixed IPs') . "</button>";
        $html .= "<button type='button' class='ipsuperview-quickfilters__btn' data-ipsuperview-quickfilter='" . $this->escape($tableName) . "' data-ipsuperview-mode='stale'>" . $this->t('一键显示可能陈旧 + 高风险陈旧', 'Show stale + high risk') . "</button>";
        $html .= "<button type='button' class='ipsuperview-quickfilters__btn' data-ipsuperview-quickfilter='" . $this->escape($tableName) . "' data-ipsuperview-mode='aged'>" . $this->t('只看高风险陈旧', 'High risk only') . "</button>";
        $html .= "</div>";
        $html .= "<table class='tab_cadre_fixehov' data-ipsuperview-table='" . $this->escape($tableName) . "'>";
        $html .= "<tr><th>IP</th><th>" . $this->t('GLPI登记名称', 'GLPI registered name') . "</th><th>" . $this->t('GLPI备注', 'GLPI comment') . "</th><th>" . $this->t('当前发现名称', 'Currently discovered name') . "</th><th>" . $this->t('MAC 地址', 'MAC address') . "</th><th>" . $this->t('资产类型', 'Asset type') . "</th><th>" . $this->t('状态', 'Status') . "</th><th>" . $this->t('GLPI最后更新', 'Last GLPI update') . "</th><th>" . $this->t('资料状态', 'Record freshness') . "</th><th>" . $this->t('实扫在线', 'Seen in scan') . "</th></tr>";

        foreach ($ips as $entry) {
            $status = $this->getStatusLabel($entry['status']);
            $scanRow = $scanIndex[$entry['ip']] ?? null;
            $isOnline = $scanRow !== null;
            $onlineLabel = $isOnline ? $this->t('在线', 'Online') : $this->t('未回应', 'No reply');
            foreach ($entry['items'] as $item) {
                $currentName = $this->formatCurrentIdentity($scanRow);
                $freshness = $this->getAssetFreshnessMeta($item['asset_updated_at'] ?? '');
                $name = trim((string) $item['dname']) !== '' ? $item['dname'] : ($item['itemtype'] . ' #' . (int) $item['on_device']);
                $searchText = strtolower(implode(' ', [
                    $entry['ip'],
                    $name,
                    (string) ($item['asset_comment'] ?? ''),
                    $currentName['search'],
                    $item['mac'],
                    $item['itemtype'],
                    $status,
                    $freshness['label'],
                    $onlineLabel,
                ]));

                $html .= "<tr data-ipsuperview-row='" . $this->escape($searchText) . "' data-ipsuperview-freshness='" . $this->escape($freshness['code']) . "' id='ipsuperview-assigned-" .
                    $this->escape($tableName) . "-" . $this->escape($entry['ip']) . "-" . (int) $item['id'] . "'>";
                $html .= "<td>" . $this->escape($entry['ip']) . "</td>";
                $html .= "<td>" . $this->renderAssetLink($item) . "</td>";
                $html .= "<td>" . $this->renderAssetComment((string) ($item['asset_comment'] ?? '')) . "</td>";
                $html .= "<td>" . $this->renderCurrentIdentity($currentName) . "</td>";
                $html .= "<td>" . ($this->renderNetworkPortLink($item) ?: $this->escape($item['mac'])) . "</td>";
                $html .= "<td>" . $this->escape($item['itemtype']) . "</td>";
                $html .= "<td><span class='ipsuperview-badge ipsuperview-badge--" . $entry['status'] . "'>" .
                    $this->escape($status) . "</span></td>";
                $html .= "<td>" . $this->renderAssetTimestamp($freshness) . "</td>";
                $html .= "<td><span class='ipsuperview-badge ipsuperview-badge--freshness-" . $freshness['code'] . "'>" .
                    $this->escape($freshness['label']) . "</span></td>";
                $html .= "<td><span class='ipsuperview-online ipsuperview-online--" . ($isOnline ? 'up' : 'down') . "'>" .
                    $onlineLabel . "</span></td>";
                $html .= "</tr>";
            }
        }

        $html .= '</table>';

        return $html;
    }

    private function renderDiscoveredDevices(array $rows)
    {
        if (count($rows) === 0) {
            return "<div class='alert alert-success'>" . $this->t('这次实扫没有发现“在线但未登记到 GLPI”的设备。', 'This scan did not find any live devices that are missing from GLPI.') . "</div>";
        }

        $html = "<div class='ipsuperview-filterbox'>";
        $html .= "<input type='search' class='form-control' placeholder='" . $this->escape($this->t('搜索发现的 IP、主机名或 MAC', 'Search discovered IPs, hostnames, or MACs')) . "' " .
            "data-ipsuperview-filter='discovered-table' />";
        $html .= "</div>";
        $html .= "<table class='tab_cadre_fixehov' data-ipsuperview-table='discovered-table'>";
        $html .= "<tr><th>IP</th><th>" . $this->t('设备名称', 'Device name') . "</th><th>" . $this->t('MAC 地址', 'MAC address') . "</th><th>" . $this->t('来源', 'Source') . "</th><th>" . $this->t('备注', 'Notes') . "</th><th>" . $this->t('最近扫描', 'Last scan') . "</th></tr>";

        foreach ($rows as $row) {
            $hostname = trim((string) $row['label']) !== ''
                ? (string) $row['label']
                : (trim((string) $row['hostname']) !== '' ? (string) $row['hostname'] : $this->t('未识别设备', 'Unidentified device'));
            $sourceParts = [];
            if (trim((string) $row['label']) !== '') {
                $sourceParts[] = $this->t('人工别名', 'Manual alias');
            }
            if (trim((string) $row['hostname']) !== '') {
                $sourceParts[] = $this->t('扫描 + 反向DNS', 'Scan + reverse DNS');
            } else {
                $sourceParts[] = $this->t('扫描', 'Scan');
            }
            $source = implode(' / ', $sourceParts);
            $searchText = strtolower(implode(' ', [
                $row['ip'],
                $hostname,
                $row['mac'],
                $source,
                $row['note'] ?? '',
            ]));

            $html .= "<tr data-ipsuperview-row='" . $this->escape($searchText) . "'>";
            $html .= "<td>" . $this->escape($row['ip']) . "</td>";
            $html .= "<td>" . $this->escape($hostname) . "</td>";
            $html .= "<td>" . $this->escape($row['mac']) . "</td>";
            $html .= "<td>" . $this->escape($source) . "</td>";
            $html .= "<td>" . $this->escape((string) ($row['note'] ?? '')) . "</td>";
            $html .= "<td>" . $this->escape($row['last_seen']) . "</td>";
            $html .= "</tr>";
        }

        $html .= '</table>';

        return $html;
    }

    private function renderScanToolbar(array $scanMeta)
    {
        if ($scanMeta['last_seen'] !== '') {
            $label = $this->t('上次实扫时间：%s', 'Last scan time: %s', [$scanMeta['last_seen']]);
            if ((int) ($scanMeta['alive_count'] ?? 0) > 0) {
                $label .= $this->t('，发现在线地址 %d 个', ', found %d live addresses', [(int) $scanMeta['alive_count']]);
            }
            if ((int) ($scanMeta['duration_ms'] ?? 0) > 0) {
                $label .= $this->t('，耗时 %s 秒', ', took %s seconds', [number_format(((int) $scanMeta['duration_ms']) / 1000, 1)]);
            }
        } else {
            $label = $this->t('还没有实扫数据，请点击右侧按钮主动扫描一次。', 'No scan data yet. Use the action on the right to run a scan.');
        }

        $html = "<div class='ipsuperview-scantoolbar'>";
        $html .= "<div class='ipsuperview-scantoolbar__label'>" . $this->escape($label) . "</div>";
        $html .= "<div class='ipsuperview-scantoolbar__actions'>";

        if (self::canUpdate()) {
            $html .= "<form method='post' action='" . $this->getFormURL() . "'>";
            $html .= Html::hidden('id', ['value' => $this->getID()]);
            $html .= Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
            $html .= "<button type='submit' name='refreshscan' value='1' class='btn btn-primary'>" . $this->t('重新实扫这个网段', 'Rescan this subnet') . "</button>";
            $html .= "</form>";
        }

        if (self::canView()) {
            $html .= "<a class='btn btn-secondary' href='" . PLUGIN_IPSUPERVIEW_WEBDIR .
                "/front/export.php?id=" . (int) $this->getID() . "'>" . $this->t('导出 CSV 报表', 'Export CSV report') . "</a>";
        }

        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }

    private function renderLabelManager()
    {
        if (!self::canUpdate()) {
            return "<div class='alert alert-info'>" . $this->t('你当前只有查看权限。如需给 IP 添加人工名称或保留标记，请使用有修改权限的账号。', 'You currently have view-only access. Use an account with update rights to add manual names or reserved flags.') . "</div>";
        }

        $html = "<form method='post' action='" . $this->getFormURL() . "' class='ipsuperview-labelform'>";
        $html .= Html::hidden('id', ['value' => $this->getID()]);
        $html .= Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);

        $html .= "<div class='ipsuperview-labelform__grid'>";
        $html .= "<div><label>" . $this->t('IP 地址', 'IP address') . "</label>" .
            Html::input('ip', ['required' => true, 'placeholder' => '192.168.32.1']) . "</div>";
        $html .= "<div><label>" . $this->t('人工名称/别名', 'Manual name / alias') . "</label>" .
            Html::input('label', ['placeholder' => $this->t('例如：生产线网关', 'Example: Production line gateway')]) . "</div>";
        $html .= "<div><label>" . $this->t('备注', 'Notes') . "</label>" .
            Html::input('note', ['placeholder' => $this->t('例如：不纳入固定IP核对', 'Example: Excluded from fixed-IP audit')]) . "</div>";
        $html .= "<div class='ipsuperview-labelform__check'><label>" . $this->t('保留/例外地址', 'Reserved / exception IP') . "</label><div>" .
            Html::getCheckbox(['name' => 'is_reserved', 'value' => 1]) . " " . $this->t('标记为保留或例外', 'Mark as reserved or exception') . "</div></div>";
        $html .= "</div>";

        $html .= "<div class='ipsuperview-labelform__actions'>";
        $html .= "<button type='submit' name='saveiplabel' value='1' class='btn btn-primary'>" . $this->t('保存这个地址标记', 'Save this IP label') . "</button>";
        $html .= "<span class='ipsuperview-formhint ipsuperview-formhint--muted'>" . $this->t('如果把名称、备注都清空，并且不勾选保留/例外，这条人工标记会被删除。', 'If you clear both name and notes and leave reserved unchecked, this manual label will be deleted.') . "</span>";
        $html .= "</div>";
        $html .= "</form>";

        return $html;
    }

    private function renderIpLabelsTable(array $labels, array $scanIndex)
    {
        if (count($labels) === 0) {
            return "<div class='alert alert-info'>" . $this->t('目前还没有人工别名或保留地址配置。', 'There are no manual aliases or reserved IP rules yet.') . "</div>";
        }

        $html = "<table class='tab_cadre_fixehov'>";
        $html .= "<tr><th>IP</th><th>" . $this->t('人工名称', 'Manual name') . "</th><th>" . $this->t('标记', 'Marker') . "</th><th>" . $this->t('备注', 'Notes') . "</th><th>" . $this->t('实扫在线', 'Seen in scan') . "</th><th>" . $this->t('最后更新', 'Last updated') . "</th></tr>";
        foreach ($labels as $row) {
            $isOnline = isset($scanIndex[$row['ip']]);
            $html .= "<tr>";
            $html .= "<td>" . $this->escape($row['ip']) . "</td>";
            $html .= "<td>" . $this->escape((string) $row['label']) . "</td>";
            $html .= "<td>" . ($row['is_reserved'] ? $this->t('保留/例外', 'Reserved / Exception') : $this->t('人工别名', 'Manual alias')) . "</td>";
            $html .= "<td>" . $this->escape((string) $row['note']) . "</td>";
            $html .= "<td><span class='ipsuperview-online ipsuperview-online--" . ($isOnline ? 'up' : 'down') . "'>" .
                ($isOnline ? $this->t('在线', 'Online') : $this->t('未回应', 'No reply')) . "</span></td>";
            $html .= "<td>" . $this->escape((string) $row['updated_at']) . "</td>";
            $html .= "</tr>";
        }
        $html .= "</table>";

        return $html;
    }

    public function buildCsvReport()
    {
        $overview = $this->computeOverview();
        $summary = $overview['summary'];
        $assignedEntries = $overview['assigned_entries'];
        $stream = fopen('php://temp', 'r+');

        fputcsv($stream, [$this->t('IP SuperView 报表', 'IP SuperView Report')]);
        fputcsv($stream, [$this->t('生成时间', 'Generated at'), date('Y-m-d H:i:s')]);
        fputcsv($stream, [$this->t('网段名称', 'Subnet name'), (string) ($this->fields['name'] ?? '')]);
        fputcsv($stream, ['CIDR', (string) ($this->fields['cidr'] ?? '')]);
        fputcsv($stream, [$this->t('地址范围', 'Address range'), (string) ($this->fields['first_ip'] ?? '') . ' - ' . (string) ($this->fields['last_ip'] ?? '')]);
        fputcsv($stream, [$this->t('上次实扫时间', 'Last scan time'), (string) ($overview['scan_meta']['last_seen'] ?? '')]);
        fputcsv($stream, []);

        fputcsv($stream, [$this->t('汇总', 'Summary')]);
        fputcsv($stream, [$this->t('指标', 'Metric'), $this->t('值', 'Value')]);
        fputcsv($stream, [$this->t('可用地址数', 'Usable addresses'), (string) $summary['host_count']]);
        fputcsv($stream, [$this->t('已占用', 'Occupied'), (string) $summary['assigned']]);
        fputcsv($stream, [$this->t('空闲', 'Free'), (string) $summary['free']]);
        fputcsv($stream, [$this->t('冲突', 'Conflicts'), (string) $summary['duplicates']]);
        fputcsv($stream, [$this->t('保留/例外', 'Reserved / Exceptions'), (string) $summary['reserved']]);
        fputcsv($stream, [$this->t('在线未登记', 'Live Unregistered'), (string) $summary['discovered_only']]);
        fputcsv($stream, [$this->t('资料陈旧', 'Stale Records'), (string) $summary['stale']]);
        fputcsv($stream, []);

        fputcsv($stream, [$this->t('固定IP设备对应表', 'Fixed IP Device Mapping')]);
        fputcsv($stream, ['IP', $this->t('GLPI登记名称', 'GLPI registered name'), $this->t('GLPI备注', 'GLPI comment'), $this->t('当前发现名称', 'Currently discovered name'), $this->t('MAC 地址', 'MAC address'), $this->t('资产类型', 'Asset type'), $this->t('状态', 'Status'), $this->t('GLPI最后更新', 'Last GLPI update'), $this->t('资料状态', 'Record freshness'), $this->t('实扫在线', 'Seen in scan')]);
        foreach ($assignedEntries as $entry) {
            $status = $this->getStatusLabel($entry['status']);
            $scanRow = $overview['scan_index'][$entry['ip']] ?? null;
            $isOnline = $scanRow !== null;
            $currentName = $this->formatCurrentIdentity($scanRow);
            foreach ($entry['items'] as $item) {
                $freshness = $this->getAssetFreshnessMeta($item['asset_updated_at'] ?? '');
                fputcsv($stream, [
                    $entry['ip'],
                    $this->getAssetDisplayName($item),
                    trim((string) ($item['asset_comment'] ?? '')),
                    $currentName['name'],
                    (string) $item['mac'],
                    (string) $item['itemtype'],
                    $status,
                    $freshness['updated_at'],
                    $freshness['label'],
                    $isOnline ? $this->t('在线', 'Online') : $this->t('未回应', 'No reply'),
                ]);
            }
        }
        fputcsv($stream, []);

        fputcsv($stream, [$this->t('异常IP', 'Conflict IPs')]);
        fputcsv($stream, [$this->t('冲突 IP', 'Conflicting IP'), $this->t('设备名称', 'Device name'), $this->t('GLPI备注', 'GLPI comment'), $this->t('GLPI最后更新', 'Last GLPI update'), $this->t('资料状态', 'Record freshness'), $this->t('MAC 地址', 'MAC address'), $this->t('资产类型', 'Asset type')]);
        foreach ($overview['duplicates'] as $entry) {
            foreach ($entry['items'] as $item) {
                $freshness = $this->getAssetFreshnessMeta($item['asset_updated_at'] ?? '');
                fputcsv($stream, [
                    $entry['ip'],
                    $this->getAssetDisplayName($item),
                    trim((string) ($item['asset_comment'] ?? '')),
                    $freshness['updated_at'],
                    $freshness['label'],
                    (string) $item['mac'],
                    (string) $item['itemtype'],
                ]);
            }
        }
        fputcsv($stream, []);

        fputcsv($stream, [$this->t('在线未登记设备', 'Live Unregistered Devices')]);
        fputcsv($stream, ['IP', $this->t('设备名称', 'Device name'), $this->t('MAC 地址', 'MAC address'), $this->t('来源', 'Source'), $this->t('备注', 'Notes'), $this->t('最近扫描', 'Last scan')]);
        foreach ($overview['discovered_only'] as $row) {
            $hostname = trim((string) ($row['label'] ?? '')) !== ''
                ? (string) $row['label']
                : (trim((string) $row['hostname']) !== '' ? (string) $row['hostname'] : $this->t('未识别设备', 'Unidentified device'));
            fputcsv($stream, [
                (string) $row['ip'],
                $hostname,
                (string) $row['mac'],
                trim((string) ($row['label'] ?? '')) !== '' ? $this->t('人工别名 / 扫描', 'Manual alias / Scan') : (trim((string) $row['hostname']) !== '' ? $this->t('扫描 + 反向DNS', 'Scan + reverse DNS') : $this->t('扫描', 'Scan')),
                (string) ($row['note'] ?? ''),
                (string) $row['last_seen'],
            ]);
        }
        fputcsv($stream, []);

        fputcsv($stream, [$this->t('人工别名和保留地址', 'Manual Aliases and Reserved IPs')]);
        fputcsv($stream, ['IP', $this->t('人工名称', 'Manual name'), $this->t('标记', 'Marker'), $this->t('备注', 'Notes'), $this->t('最后更新', 'Last updated')]);
        foreach ($overview['labels'] as $row) {
            fputcsv($stream, [
                (string) $row['ip'],
                (string) $row['label'],
                ((int) $row['is_reserved'] === 1) ? $this->t('保留/例外', 'Reserved / Exception') : $this->t('人工别名', 'Manual alias'),
                (string) $row['note'],
                (string) $row['updated_at'],
            ]);
        }

        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }

    public function getReportFilename()
    {
        $baseName = trim((string) ($this->fields['name'] ?? 'subnet-report'));
        $baseName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $baseName);
        $baseName = trim((string) $baseName, '-');
        if ($baseName === '') {
            $baseName = 'subnet-report';
        }

        return $baseName . '-' . date('Ymd-His') . '.csv';
    }

    private function renderAssetLink(array $item)
    {
        $label = $this->escape($this->getAssetDisplayName($item));
        $url = Toolbox::getItemTypeFormURL($item['itemtype']);

        return "<a href='" . $url . '?id=' . (int) $item['on_device'] . "'>" . $label . '</a>';
    }

    private function renderNetworkPortLink(array $item)
    {
        global $CFG_GLPI;

        if ((int) $item['id'] === 0 || trim((string) $item['mac']) === '') {
            return '';
        }

        return "<a href='" . $CFG_GLPI['root_doc'] . "/front/networkport.form.php?id=" . (int) $item['id'] . "'>" .
            $this->escape($item['mac']) . '</a>';
    }

    private function renderAssetComment($comment)
    {
        $comment = trim((string) $comment);
        if ($comment === '') {
            return "<span class='ipsuperview-subtle'>-</span>";
        }

        return nl2br($this->escape($comment));
    }

    private function getStatusLabel($status)
    {
        switch ($status) {
            case 'assigned':
                return $this->t('已占用', 'Occupied');
            case 'duplicate':
                return $this->t('冲突', 'Conflict');
            default:
                return $this->t('空闲', 'Free');
        }
    }

    public static function canCreate()
    {
        return Session::haveRight(self::$rightname, CREATE);
    }

    public static function canView()
    {
        return Session::haveRight(self::$rightname, READ);
    }

    public static function canUpdate()
    {
        return Session::haveRight(self::$rightname, UPDATE);
    }

    public static function canDelete()
    {
        return Session::haveRight(self::$rightname, PURGE);
    }

    public function saveIpLabel(array $input)
    {
        global $DB;

        self::ensureRuntimeTables();
        $ip = trim((string) ($input['ip'] ?? ''));
        $label = trim((string) ($input['label'] ?? ''));
        $note = trim((string) ($input['note'] ?? ''));
        $isReserved = isset($input['is_reserved']) ? 1 : 0;

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return ['ok' => false, 'message' => $this->t('IP 地址格式不正确。', 'Invalid IP address format.')];
        }

        $range = self::parseCidr($this->fields['cidr']);
        $ipNum = (int) sprintf('%u', ip2long($ip));
        if ($range === false || $ipNum < $range['first_ip_num'] || $ipNum > $range['last_ip_num']) {
            return ['ok' => false, 'message' => $this->t('这个 IP 不在当前网段范围内。', 'This IP is outside the current subnet range.')];
        }

        $criteria = [
            'plugin_ipsuperview_subnets_id' => $this->getID(),
            'ip' => $ip,
        ];

        if ($label === '' && $note === '' && $isReserved === 0) {
            $DB->delete('glpi_plugin_ipsuperview_iplabels', $criteria);
            return ['ok' => true, 'message' => $this->t('已删除这个地址的人工标记。', 'The manual label for this IP was removed.')];
        }

        $existing = $DB->request([
            'FROM' => 'glpi_plugin_ipsuperview_iplabels',
            'WHERE' => $criteria,
            'LIMIT' => 1,
        ])->current();

        $row = [
            'plugin_ipsuperview_subnets_id' => $this->getID(),
            'ip' => $ip,
            'label' => $label,
            'note' => $note,
            'is_reserved' => $isReserved,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $row['id'] = (int) $existing['id'];
            $DB->update('glpi_plugin_ipsuperview_iplabels', $row, ['id' => (int) $existing['id']]);
        } else {
            $DB->insert('glpi_plugin_ipsuperview_iplabels', $row);
        }

        return ['ok' => true, 'message' => $this->t('人工标记已保存。', 'The manual label was saved.')];
    }

    private function getAssetDisplayName(array $item)
    {
        return trim((string) $item['dname']) !== ''
            ? (string) $item['dname']
            : ((string) $item['itemtype'] . ' #' . (int) $item['on_device']);
    }

    private function formatCurrentIdentity($scanRow)
    {
        if (!is_array($scanRow)) {
            return [
                'name' => $this->t('本次未发现', 'Not seen this time'),
                'source' => $this->t('无实扫结果', 'No scan result'),
                'search' => $this->t('本次未发现 无实扫结果', 'Not seen this time No scan result'),
            ];
        }

        if (trim((string) ($scanRow['label'] ?? '')) !== '') {
            $name = (string) $scanRow['label'];
            $source = $this->t('人工别名', 'Manual alias');
        } elseif (trim((string) ($scanRow['hostname'] ?? '')) !== '') {
            $name = (string) $scanRow['hostname'];
            $source = $this->t('扫描 + 反向DNS', 'Scan + reverse DNS');
        } else {
            $name = $this->t('在线但未识别', 'Online but unidentified');
            $source = $this->t('扫描', 'Scan');
        }

        return [
            'name' => $name,
            'source' => $source,
            'search' => trim($name . ' ' . $source . ' ' . (string) ($scanRow['mac'] ?? '')),
        ];
    }

    private function renderCurrentIdentity(array $identity)
    {
        $html = "<div class='ipsuperview-identity'>";
        $html .= "<strong>" . $this->escape($identity['name']) . "</strong>";
        $html .= "<span>" . $this->escape($identity['source']) . "</span>";
        $html .= "</div>";

        return $html;
    }

    private function getAssetFreshnessMeta($updatedAt)
    {
        $updatedAt = trim((string) $updatedAt);
        if ($updatedAt === '' || $updatedAt === '0000-00-00 00:00:00') {
            return [
                'code' => 'unknown',
                'label' => $this->t('更新时间未知', 'Update time unknown'),
                'days' => null,
                'updated_at' => '',
            ];
        }

        try {
            $updated = new DateTime($updatedAt);
            $now = new DateTime();
            $days = (int) $updated->diff($now)->days;
        } catch (Exception $e) {
            return [
                'code' => 'unknown',
                'label' => $this->t('更新时间未知', 'Update time unknown'),
                'days' => null,
                'updated_at' => $updatedAt,
            ];
        }

        if ($days >= 30) {
            $label = $this->t('高风险陈旧', 'High risk stale');
            $code = 'aged';
        } elseif ($days >= 14) {
            $label = $this->t('可能陈旧', 'Possibly stale');
            $code = 'stale';
        } else {
            $label = $this->t('较新', 'Recent');
            $code = 'fresh';
        }

        return [
            'code' => $code,
            'label' => $label,
            'days' => $days,
            'updated_at' => $updated->format('Y-m-d H:i:s'),
        ];
    }

    private function renderAssetTimestamp(array $freshness)
    {
        if ($freshness['updated_at'] === '') {
            return "<span class='ipsuperview-subtle'>" . $this->t('GLPI 没有提供更新时间', 'GLPI did not provide an update time') . "</span>";
        }

        $html = "<div class='ipsuperview-timestamp'>";
        $html .= "<strong>" . $this->escape($freshness['updated_at']) . "</strong>";
        if ($freshness['days'] !== null) {
            $html .= "<span>" . $this->t('%d 天前', '%d days ago', [(int) $freshness['days']]) . "</span>";
        }
        $html .= "</div>";

        return $html;
    }

    private function computeOverview()
    {
        global $DB;

        self::ensureRuntimeTables();
        $range = self::parseCidr($this->fields['cidr']);
        $observed = [];
        $labelIndex = $this->getIpLabels();

        $dbu = new DbUtils();
        foreach (self::getSupportedItemTypes() as $type) {
            $item = $dbu->getItemForItemtype($type);
            if (!$item) {
                continue;
            }

            $itemtable = $dbu->getTableForItemType($type);
            $userField = in_array($type, ['Enclosure', 'PDU', 'Cluster', 'Unmanaged'], true)
                ? '0 AS users_id'
                : '`dev`.`users_id`';
            $commentField = $DB->fieldExists($itemtable, 'comment')
                ? "COALESCE(`dev`.`comment`, '') AS `asset_comment`"
                : "'' AS `asset_comment`";
            $sql = "SELECT `port`.`id`,
                           '" . $type . "' AS `itemtype`,
                           `port`.`items_id` AS `on_device`,
                           `dev`.`name` AS `dname`,
                           COALESCE(`dev`.`date_mod`, `dev`.`date_creation`, '') AS `asset_updated_at`,
                           " . $commentField . ",
                           COALESCE(`port`.`name`, '') AS `pname`,
                           `glpi_ipaddresses`.`name` AS `ip`,
                           `port`.`mac`,
                           " . $userField . ",
                           INET_ATON(`glpi_ipaddresses`.`name`) AS `ipnum`
                    FROM `glpi_networkports` `port`
                    LEFT JOIN `" . $itemtable . "` `dev`
                        ON (`port`.`items_id` = `dev`.`id` AND `port`.`itemtype` = '" . $type . "')
                    LEFT JOIN `glpi_networknames`
                        ON (`port`.`id` = `glpi_networknames`.`items_id`)
                    LEFT JOIN `glpi_ipaddresses`
                        ON (`glpi_ipaddresses`.`items_id` = `glpi_networknames`.`id`)
                    WHERE `glpi_ipaddresses`.`name` IS NOT NULL
                      AND `glpi_ipaddresses`.`name` != ''
                      AND `glpi_ipaddresses`.`version` = 4
                      AND INET_ATON(`glpi_ipaddresses`.`name`) BETWEEN '" . $range['first_ip_num'] . "' AND '" . $range['last_ip_num'] . "'";

            $sql .= $dbu->getEntitiesRestrictRequest(' AND ', 'dev', 'entities_id', $this->fields['entities_id']);

            if ($item->maybeDeleted()) {
                $sql .= " AND `dev`.`is_deleted` = 0";
            }

            if ($item->maybeTemplate()) {
                $sql .= " AND `dev`.`is_template` = 0";
            }

            $sql .= " GROUP BY `ip`, `port`.`mac`
                      ORDER BY `ipnum`";

            foreach ($DB->request($sql) as $row) {
                $observed[(int) $row['ipnum']][] = $row;
            }
        }

        $scanRows = $this->getLiveScanRows();
        $scanMeta = $this->getLastScanMeta();

        $duplicates = [];
        $assigned = 0;
        $assignedEntries = [];
        $staleAssigned = [];
        foreach ($observed as $ipnum => $items) {
            if (count($items) === 0) {
                continue;
            }

            $ip = self::unsignedIntToIp($ipnum);
            $assigned++;
            $assignedEntries[] = [
                'ip'    => $ip,
                'status' => count($items) > 1 ? 'duplicate' : 'assigned',
                'items' => $items,
            ];
            foreach ($items as $item) {
                $freshness = $this->getAssetFreshnessMeta($item['asset_updated_at'] ?? '');
                if (in_array($freshness['code'], ['stale', 'aged', 'unknown'], true)) {
                    $staleAssigned[$ip] = true;
                    break;
                }
            }
            if (count($items) > 1) {
                $duplicates[] = [
                    'ip'    => $ip,
                    'items' => $items,
                ];
            }
        }

        $scanIndex = [];
        $discoveredOnly = [];
        foreach ($scanRows as $row) {
            if (isset($labelIndex[$row['ip']])) {
                $row['label'] = $labelIndex[$row['ip']]['label'];
                $row['note'] = $labelIndex[$row['ip']]['note'];
                $row['is_reserved'] = (int) $labelIndex[$row['ip']]['is_reserved'];
            } else {
                $row['label'] = '';
                $row['note'] = '';
                $row['is_reserved'] = 0;
            }
            $scanIndex[$row['ip']] = $row;
            $ipnum = (int) sprintf('%u', ip2long($row['ip']));
            if ((!isset($observed[$ipnum]) || count($observed[$ipnum]) === 0) && (int) $row['is_reserved'] === 0) {
                $discoveredOnly[] = $row;
            }
        }

        $summary = [
            'host_count'      => $range['host_count'],
            'assigned'        => $assigned,
            'duplicates'      => count($duplicates),
            'free'            => max(0, $range['host_count'] - $assigned),
            'reserved'        => count(array_filter($labelIndex, function ($row) {
                return (int) $row['is_reserved'] === 1;
            })),
            'discovered_only' => count($discoveredOnly),
            'stale'           => count($staleAssigned),
        ];

        $ips = [];
        if ($range['host_count'] <= 512) {
            for ($ipnum = $range['first_ip_num']; $ipnum <= $range['last_ip_num']; $ipnum++) {
                $items = $observed[$ipnum] ?? [];
                $status = 'free';
                if (count($items) === 1) {
                    $status = 'assigned';
                } elseif (count($items) > 1) {
                    $status = 'duplicate';
                }

                $ips[] = [
                    'ip'     => self::unsignedIntToIp($ipnum),
                    'status' => $status,
                    'items'  => $items,
                ];
            }
        }

        return [
            'summary'         => $summary,
            'assigned_entries'=> $assignedEntries,
            'duplicates'      => $duplicates,
            'ips'             => $ips,
            'discovered_only' => $discoveredOnly,
            'labels'          => array_values($labelIndex),
            'scan_index'      => $scanIndex,
            'scan_meta'       => [
                'last_seen' => $scanMeta['last_seen'] ?? (count($scanRows) > 0 ? $scanRows[0]['last_seen'] : ''),
                'alive_count' => (int) ($scanMeta['alive_count'] ?? count($scanRows)),
                'duration_ms' => (int) ($scanMeta['duration_ms'] ?? 0),
                'status' => (string) ($scanMeta['status'] ?? ''),
            ],
        ];
    }

    private static function ensureRuntimeTables()
    {
        global $DB;

        if (!$DB->tableExists('glpi_plugin_ipsuperview_scans')) {
            $DB->query("CREATE TABLE `glpi_plugin_ipsuperview_scans` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `plugin_ipsuperview_subnets_id` int unsigned NOT NULL,
                `ip` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
                `mac` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `hostname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `last_seen` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `subnet_ip` (`plugin_ipsuperview_subnets_id`, `ip`),
                KEY `last_seen` (`last_seen`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");
        }

        if (!$DB->tableExists('glpi_plugin_ipsuperview_scanmeta')) {
            $DB->query("CREATE TABLE `glpi_plugin_ipsuperview_scanmeta` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `plugin_ipsuperview_subnets_id` int unsigned NOT NULL,
                `last_seen` datetime DEFAULT NULL,
                `duration_ms` int unsigned NOT NULL DEFAULT 0,
                `alive_count` int unsigned NOT NULL DEFAULT 0,
                `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'idle',
                `message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `subnet_once` (`plugin_ipsuperview_subnets_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");
        }

        if (!$DB->tableExists('glpi_plugin_ipsuperview_iplabels')) {
            $DB->query("CREATE TABLE `glpi_plugin_ipsuperview_iplabels` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `plugin_ipsuperview_subnets_id` int unsigned NOT NULL,
                `ip` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
                `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `is_reserved` tinyint(1) NOT NULL DEFAULT 0,
                `updated_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `subnet_iplabel` (`plugin_ipsuperview_subnets_id`, `ip`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");
        }
    }

    public function refreshLiveScan()
    {
        global $DB;

        self::ensureRuntimeTables();
        $range = self::parseCidr($this->fields['cidr']);
        if ($range === false || $range['host_count'] > 1024) {
            return ['ok' => false, 'message' => $this->t('当前网段不支持实扫。', 'Live scanning is not supported for this subnet.')];
        }
        if (!function_exists('shell_exec')) {
            return ['ok' => false, 'message' => $this->t('当前 PHP 环境禁用了 shell_exec。', 'The current PHP environment has shell_exec disabled.')];
        }

        $startedAt = microtime(true);
        $cidr = escapeshellarg($range['cidr']);
        $aliveOutput = (string) shell_exec("fping -a -r0 -t50 -g " . $cidr . " 2>/dev/null | sort");
        $aliveIps = array_values(array_filter(array_map('trim', preg_split('/\R+/', $aliveOutput))));

        $neighOutput = (string) shell_exec("ip neigh show");
        $macByIp = [];
        foreach (preg_split('/\R+/', $neighOutput) as $line) {
            if (!preg_match('/^(\d+\.\d+\.\d+\.\d+)\s+dev\s+\S+\s+lladdr\s+([0-9a-f:]{17})\s+/i', trim($line), $matches)) {
                continue;
            }
            $macByIp[$matches[1]] = strtolower($matches[2]);
        }

        $DB->delete('glpi_plugin_ipsuperview_scans', ['plugin_ipsuperview_subnets_id' => $this->getID()]);
        $now = date('Y-m-d H:i:s');

        foreach ($aliveIps as $ip) {
            $DB->insert('glpi_plugin_ipsuperview_scans', [
                'plugin_ipsuperview_subnets_id' => $this->getID(),
                'ip'                            => $ip,
                'mac'                           => $macByIp[$ip] ?? '',
                'hostname'                      => '',
                'last_seen'                     => $now,
            ]);
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $DB->delete('glpi_plugin_ipsuperview_scanmeta', ['plugin_ipsuperview_subnets_id' => $this->getID()]);
        $DB->insert('glpi_plugin_ipsuperview_scanmeta', [
            'plugin_ipsuperview_subnets_id' => $this->getID(),
            'last_seen' => $now,
            'duration_ms' => $durationMs,
            'alive_count' => count($aliveIps),
            'status' => 'ok',
            'message' => '',
        ]);

        return [
            'ok' => true,
            'alive_count' => count($aliveIps),
            'duration_ms' => $durationMs,
            'message' => '',
        ];
    }

    private function getLiveScanRows()
    {
        global $DB;

        self::ensureRuntimeTables();
        $rows = [];
        foreach ($DB->request([
            'FROM'  => 'glpi_plugin_ipsuperview_scans',
            'WHERE' => ['plugin_ipsuperview_subnets_id' => $this->getID()],
            'ORDER' => ['ip ASC'],
        ]) as $row) {
            $rows[] = $row;
        }

        usort($rows, function ($left, $right) {
            return ((int) sprintf('%u', ip2long($left['ip']))) <=> ((int) sprintf('%u', ip2long($right['ip'])));
        });

        return $rows;
    }

    private function getLastScanMeta()
    {
        global $DB;

        self::ensureRuntimeTables();
        $meta = $DB->request([
            'FROM' => 'glpi_plugin_ipsuperview_scanmeta',
            'WHERE' => ['plugin_ipsuperview_subnets_id' => $this->getID()],
            'LIMIT' => 1,
        ])->current();

        return $meta ?: [];
    }

    private function getIpLabels()
    {
        global $DB;

        self::ensureRuntimeTables();
        $rows = [];
        foreach ($DB->request([
            'FROM' => 'glpi_plugin_ipsuperview_iplabels',
            'WHERE' => ['plugin_ipsuperview_subnets_id' => $this->getID()],
            'ORDER' => ['ip ASC'],
        ]) as $row) {
            $rows[$row['ip']] = $row;
        }

        uksort($rows, function ($left, $right) {
            return ((int) sprintf('%u', ip2long($left))) <=> ((int) sprintf('%u', ip2long($right)));
        });

        return $rows;
    }

    private static function getSupportedItemTypes()
    {
        return [
            'Computer',
            'NetworkEquipment',
            'Peripheral',
            'Phone',
            'Printer',
            'Enclosure',
            'PDU',
            'Cluster',
        ];
    }

    private function escape($value)
    {
        return htmlspecialchars(Toolbox::stripTags((string) $value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function parseCidr($cidr)
    {
        $cidr = trim((string) $cidr);
        if (!preg_match('/^(\d{1,3}(?:\.\d{1,3}){3})\/(\d{1,2})$/', $cidr, $matches)) {
            return false;
        }

        $ip = $matches[1];
        $prefix = (int) $matches[2];

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        if ($prefix < 20 || $prefix > 30) {
            return false;
        }

        $base = (int) sprintf('%u', ip2long($ip));
        $mask = $prefix === 0 ? 0 : ((~0 << (32 - $prefix)) & 0xFFFFFFFF);
        $network = $base & $mask;
        $broadcast = $network | (~$mask & 0xFFFFFFFF);
        $first = $network + 1;
        $last = $broadcast - 1;
        $hostCount = max(0, $last - $first + 1);

        if ($hostCount <= 0) {
            return false;
        }

        return [
            'cidr'         => self::unsignedIntToIp($network) . '/' . $prefix,
            'prefix'       => $prefix,
            'first_ip'     => self::unsignedIntToIp($first),
            'last_ip'      => self::unsignedIntToIp($last),
            'first_ip_num' => $first,
            'last_ip_num'  => $last,
            'host_count'   => $hostCount,
        ];
    }

    public static function unsignedIntToIp($ipnum)
    {
        $ipnum = (int) $ipnum;
        if ($ipnum > 2147483647) {
            $ipnum -= 4294967296;
        }

        return long2ip($ipnum);
    }
}
