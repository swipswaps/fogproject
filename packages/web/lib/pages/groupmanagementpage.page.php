<?php
/**
 * Group management page
 *
 * PHP version 5
 *
 * The group represented to the GUI
 *
 * @category GroupManagementPage
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Group management page
 *
 * The group represented to the GUI
 *
 * @category GroupManagementPage
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class GroupManagementPage extends FOGPage
{
    private static $_common = [];
    /**
     * The node that uses this class
     *
     * @var string
     */
    public $node = 'group';
    /**
     * Initializes the group page
     *
     * @param string $name the name to construct with
     *
     * @return void
     */
    public function __construct($name = '')
    {
        $this->name = 'Group Management';
        parent::__construct($this->name);
        if ($this->obj) {
            $this->_getHostCommon();
        }
        $this->headerData = [
            _('Name'),
            _('Members')
        ];
        $this->templates = [
            '',
            ''
        ];
        $this->attributes = [
            [],
            ['width' => 5]
        ];
    }
    /**
     * Get host common items
     *
     * @return void
     */
    private function _getHostCommon()
    {
        $HostCount = $this->obj->getHostCount();
        $hostids = $this->obj->get('hosts');
        $getItems = [
            'imageID',
            'productKey',
            'printerLevel',
            'useAD',
            'enforce',
            'ADDomain',
            'ADOU',
            'ADUser',
            'ADPass',
            'biosexit',
            'efiexit',
        ];
        foreach ($getItems as &$idField) {
            $tmp = self::getClass('HostManager')
                ->distinct(
                    $idField,
                    ['id' => $hostids]
                );
            self::$_common[] = (bool)($tmp == 1);
            unset($idField);
        }
        self::$Host = new Host(max($hostids));
    }
    /**
     * Create a new group.
     *
     * @return void
     */
    public function add()
    {
        $this->title = _('Create New Group');

        $group = filter_input(INPUT_POST, 'group');
        $description = filter_input(INPUT_POST, 'description');
        $kern = filter_input(INPUT_POST, 'kern');
        $args = filter_input(INPUT_POST, 'args');
        $init = filter_input(INPUT_POST, 'init');
        $dev = filter_input(INPUT_POST, 'dev');

        $labelClass = 'col-sm-2 control-label';

        // The fields to display
        $fields = [
            self::makeLabel(
                $labelClass,
                'group',
                _('Group Name')
            ) => self::makeInput(
                'form-control groupname-input',
                'group',
                _('Group Name'),
                'text',
                'group',
                $group,
                true
            ),
            self::makeLabel(
                $labelClass,
                'description',
                _('Group Description')
            ) => self::makeTextarea(
                'form-control groupdescription-input',
                'description',
                _('Group Description'),
                'description',
                $description
            ),
            self::makeLabel(
                $labelClass,
                'kern',
                _('Group Kernel')
            ) => self::makeInput(
                'form-control groupkernel-input',
                'kern',
                'customBzimage',
                'text',
                'kern',
                $kern
            ),
            self::makeLabel(
                $labelClass,
                'args',
                _('Group Kernel Arguments')
            ) => self::makeInput(
                'form-control groupkernelargs-input',
                'args',
                'debug acpi=off',
                'text',
                'args',
                $args
            ),
            self::makeLabel(
                $labelClass,
                'init',
                _('Group Init')
            ) => self::makeInput(
                'form-control groupinit-input',
                'init',
                'customInit.xz',
                'text',
                'init',
                $init
            ),
            self::makeLabel(
                $labelClass,
                'dev',
                _('Group Primary Disk')
            ) => self::makeInput(
                'form-control groupdev-input',
                'dev',
                '/dev/md0',
                'text',
                'dev',
                $dev
            )
        ];

        $buttons = self::makeButton(
            'send',
            _('Create'),
            'btn btn-primary'
        );

        self::$HookManager->processEvent(
            'GROUP_ADD_FIELDS',
            [
                'fields' => &$fields,
                'buttons' => &$buttons,
                'Group' => self::getClass('Group')
            ]
        );
        $rendered = self::formFields($fields);
        unset($fields);

        echo self::makeFormTag(
            'form-horizontal',
            'group-create-form',
            $this->formAction,
            'post',
            'application/x-www-form-urlencoded',
            true
        );
        echo '<div class="box box-solid" id="group-create">';
        echo '<div class="box-body">';
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Create New Group');
        echo '</h4>';
        echo '</div>';
        echo '<div class="box-body">';
        echo $rendered;
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="box-footer with-border">';
        echo $buttons;
        echo '</div>';
        echo '</div>';
        echo '</form>';
    }
    /**
     * When submitted to add post this is what's run
     *
     * @return void
     */
    public function addPost()
    {
        header('Content-type: application/json');
        self::$HookManager->processEvent('GROUP_ADD_POST');
        $group = trim(
            filter_input(INPUT_POST, 'group')
        );
        $description = trim(
            filter_input(INPUT_POST, 'description')
        );
        $kern = trim(
            filter_input(INPUT_POST, 'kern')
        );
        $args = trim(
            filter_input(INPUT_POST, 'args')
        );
        $init = trim(
            filter_input(INPUT_POST, 'init')
        );
        $dev = trim(
            filter_input(INPUT_POST, 'dev')
        );

        $serverFault = false;
        try {
            if (!$group) {
                throw new Exception(
                    _('A group name is required!')
                );
            }
            if (self::getClass('GroupManager')->exists($group)) {
                throw new Exception(
                    _('A group already exists with this name!')
                );
            }
            $Group = self::getClass('Group')
                ->set('name', $group)
                ->set('description', $description)
                ->set('kernel', $kern)
                ->set('kernelArgs', $args)
                ->set('kernelDevice', $dev)
                ->set('init', $init);
            if (!$Group->save()) {
                $serverFault = true;
                throw new Exception(_('Add group failed!'));
            }
            $code = HTTPResponseCodes::HTTP_CREATED;
            $hook = 'GROUP_ADD_SUCCESS';
            $msg = json_encode(
                [
                    'msg' => _('Group added!'),
                    'title' => _('Group Create Success')
                ]
            );
        } catch (Exception $e) {
            $code = (
                $serverFault ?
                HTTPResponseCodes::HTTP_INTERNAL_SERVER_ERROR :
                HTTPResponseCodes::HTTP_BAD_REQUEST
            );
            $hook = 'GROUP_ADD_FAIL';
            $msg = json_encode(
                [
                    'error' => $e->getMessage(),
                    'title' => _('Group Create Fail')
                ]
            );
        }
        //header(
        //    'Location: ../management/index.php?node=group&sub=edit&id='
        //    . $Group->get('id')
        //);
        self::$HookManager->processEvent(
            $hook,
            [
                'Group' => &$Group,
                'hook' => &$hook,
                'code' => &$code,
                'msg' => &$msg,
                'serverFault' => &$serverFault
            ]
        );
        http_response_code($code);
        unset($Group);
        echo $msg;
        exit;
    }
    /**
     * Displays the group general tab.
     *
     * @return void
     */
    public function groupGeneral()
    {
        list(
            $imageIDs,
            $groupKey,
            $printerLevel,
            $aduse,
            $enforcetest,
            $adDomain,
            $adOU,
            $adUser,
            $adPass,
            $biosExit,
            $efiExit
        ) = self::$_common;
        $exitNorm = Service::buildExitSelector(
            'bootTypeExit',
            (
                filter_input(INPUT_POST, 'bootTypeExit') ?: (
                    $biosExit ?
                    self::$Host->get('biosexit') :
                    ''
                )
            ),
            true,
            'bootTypeExit'
        );
        $exitEfi = Service::buildExitSelector(
            'efiBootTypeExit',
            (
                filter_input(INPUT_POST, 'efiBootTypeExit') ?: (
                    $efiExit ?
                    self::$Host->get('efiexit') :
                    ''
                )
            ),
            true,
            'efiBootTypeExit'
        );
        $group = (
            filter_input(INPUT_POST, 'group') ?:
            $this->obj->get('name')
        );
        $description = (
            filter_input(INPUT_POST, 'description') ?:
            $this->obj->get('description')
        );
        $productKey = (
            filter_input(INPUT_POST, 'key') ?: (
                $groupKey ?
                self::$Host->get('productKey') :
                ''
            )
        );
        $productKeytest = self::aesdecrypt($productKey);
        $test_base64 = base64_decode($productKeytest);
        $base64 = mb_detect_encoding(
            $test_base64,
            'utf-8',
            true
        );
        $encKey = mb_detect_encoding(
            $productKeyTest,
            'utf-8',
            true
        );
        if ($base64) {
            $productKey = $test_base64;
        } elseif ($encKey) {
            $productKey = $productKeytest;
        }
        $kern = (
            filter_input(INPUT_POST, 'kern') ?: (
                $kern ?
                self::$Host->get('kernel') :
                $this->obj->get('kernel')
            )
        );
        $args = (
            filter_input(INPUT_POST, 'args') ?: (
                self::$Host->get('kernelArgs') ?:
                $this->obj->get('kernelArgs')
            )
        );
        $init = (
            filter_input(INPUT_POST, 'init') ?: (
                self::$Host->get('init') ?:
                $this->obj->get('init')
            )
        );
        $dev = (
            filter_input(INPUT_POST, 'dev') ?: (
                self::$Host->get('kernelDevice') ?:
                $this->obj->get('kernelDevice')
            )
        );
        $modalresetBtn = self::makeButton(
            'resetencryptionConfirm',
            _('Confirm'),
            'btn btn-primary',
            ' method="post" action="../management/index.php?sub=clearAES" '
        );
        $modalresetBtn .= self::makeButton(
            'resetencryptionCancel',
            _('Cancel'),
            'btn btn-danger pull-right'
        );
        $modalreset = self::makeModal(
            'resetencryptionmodal',
            _('Reset Encryption Data'),
            _(
                'Resetting encryption data should only be done '
                . 'if you re-installed the FOG Client or are using Debugger'
            ),
            $modalresetBtn
        );

        $labelClass = 'col-sm-2 control-label';

        $fields = [
            self::makeLabel(
                $labelClass,
                'group',
                _('Group Name')
            ) => self::makeInput(
                'form-control groupname-input',
                'group',
                _('Group Name'),
                'text',
                'group',
                $group,
                true
            ),
            self::makeLabel(
                $labelClass,
                'description',
                _('Group Description')
            ) => self::makeTextarea(
                'form-control groupdescription-input',
                'description',
                _('Group Description'),
                'description',
                $description
            ),
            self::makeLabel(
                $labelClass,
                'key',
                _('Group Product Key')
            ) => self::makeInput(
                'form-control groupkey-input',
                'key',
                'ABCDE-FGHIJ-KLMNO-PQRST-UVWXY',
                'text',
                'key',
                $productKey,
                false,
                false,
                -1,
                29,
                'exactlength="25"'
            ),
            self::makeLabel(
                $labelClass,
                'kern',
                _('Group Kernel')
            ) => self::makeInput(
                'form-control groupkern-input',
                'kern',
                'customBzimage',
                'text',
                'kern',
                $kern
            ),
            self::makeLabel(
                $labelClass,
                'args',
                _('Group Kernel Arguments')
            ) => self::makeInput(
                'form-control groupkernelargs-input',
                'args',
                'debug acpi=off',
                'text',
                'args',
                $args
            ),
            self::makeLabel(
                $labelClass,
                'init',
                _('Group Init')
            ) => self::makeInput(
                'form-control groupinit-input',
                'init',
                'customInit.xz',
                'text',
                'init',
                $init
            ),
            self::makeLabel(
                $labelClass,
                'dev',
                _('Group Primary Disk')
            ) => self::makeInput(
                'form-control groupdev-input',
                'dev',
                '/dev/md0',
                'text',
                'dev',
                $dev
            ),
            self::makeLabel(
                $labelClass,
                'bootTypeExit',
                _('Group BIOS Exit')
            ) => $exitNorm,
            self::makeLabel(
                $labelClass,
                'efiBootTypeExit',
                _('Group EFI Exit')
            ) => $exitEfi
        ];

        $buttons = '<div class="btn-group">';
        $buttons .= self::makeButton(
            'general-send',
            _('Update'),
            'btn btn-primary'
        );
        $buttons .= self::makeButton(
            'reset-encryption-data',
            _('Reset Encryption Data'),
            'btn btn-warning'
        );
        $buttons .= self::makeButton(
            'general-delete',
            _('Delete'),
            'btn btn-danger'
        );
        $buttons .= '</div>';

        self::$HookManager->processEvent(
            'GROUP_GENERAL_FIELDS',
            [
                'fields' => &$fields,
                'buttons' => &$buttons,
                'Group' => &$this->obj
            ]
        );
        $rendered = self::formFields($fields);
        unset($fields);

        echo self::makeFormTag(
            'form-horizontal',
            'group-general-form',
            self::makeTabUpdateURL(
                'group-general',
                $this->obj->get('id')
            ),
            'post',
            'application/x-www-form-urlencoded',
            true
        );
        echo '<div class="box box-solid">';
        echo '<div class="box-body">';
        echo $rendered;
        echo '</div>';
        echo '<div class="box-footer">';
        echo $buttons;
        echo $modalreset;
        echo '</div>';
        echo '</div>';
        echo '</form>';
    }
    /**
     * Group general post element
     *
     * @return void
     */
    public function groupGeneralPost()
    {
        $group = trim(
            filter_input(INPUT_POST, 'group')
        );
        $desc = trim(
            filter_input(INPUT_POST, 'description')
        );
        $key = strtoupper(
            trim(
                filter_input(INPUT_POST, 'key')
            )
        );
        $productKey = preg_replace(
            '/([\w+]{5})/',
            '$1-',
            str_replace(
                '-',
                '',
                $key
            )
        );
        $productKey = substr($productKey, 0, 29);
        $kern = trim(
            filter_input(INPUT_POST, 'kern')
        );
        $args = trim(
            filter_input(INPUT_POST, 'args')
        );
        $dev = trim(
            filter_input(INPUT_POST, 'dev')
        );
        $init = trim(
            filter_input(INPUT_POST, 'init')
        );
        $bte = trim(
            filter_input(INPUT_POST, 'bootTypeExit')
        );
        $ebte = trim(
            filter_input(INPUT_POST, 'efiBootTypeExit')
        );
        if ($group != $this->obj->get('name')) {
            if ($this->obj->getManager()->exists($group)) {
                throw new Exception(_('Please use another group name'));
            }
        }
        $this->obj
            ->set('name', $group)
            ->set('description', $desc)
            ->set('kernel', $kern)
            ->set('kernelArgs', $args)
            ->set('kernelDevice', $dev)
            ->set('init', $init);
        self::getClass('HostManager')
            ->update(
                ['id' => $this->obj->get('hosts')],
                '',
                [
                    'kernel' => $kern,
                    'kernelArgs' => $args,
                    'kernelDevice' => $dev,
                    'init' => $init,
                    'efiexit' => $ebte,
                    'biosexit' => $bte,
                    'productKey' => trim($productKey)
                ]
            );
    }
    /**
     * Prints the group image element.
     *
     * @return void
     */
    public function groupImage()
    {
        $imageID = (
            self::$_common[0] ?
            self::$Host->get('imageID') :
            ''
        );
        // Group Images
        $imageSelector = self::getClass('ImageManager')
            ->buildSelectBox($imageID, 'image');

        $labelClass = 'col-sm-2 control-label';

        $fields = [
            self::makeLabel(
                $labelClass,
                'image',
                _('Group Image')
            ) => $imageSelector
        ];

        $buttons = self::makeButton(
            'image-send',
            _('Update'),
            'btn btn-primary'
        );

        self::$HookManager->processEvent(
            'GROUP_IMAGE_FIELDS',
            [
                'fields' => &$fields,
                'buttons' => &$buttons,
                'Group' => &$this->obj
            ]
        );
        $rendered = self::formFields($fields);
        unset($fields);

        echo self::makeFormTag(
            'form-horizontal',
            'group-image-form',
            self::makeTabUpdateURL(
                'group-image',
                $this->obj->get('id')
            ),
            'post',
            'application/x-www-form-urlencoded',
            true
        );
        echo '<div class="box box-solid">';
        echo '<div class="box-body">';
        echo $rendered;
        echo '</div>';
        echo '<div class="box-footer">';
        echo $buttons;
        echo '</div>';
        echo '</div>';
        echo '</form>';
    }
    /**
     * Group image post element
     *
     * @return void
     */
    public function groupImagePost()
    {
        $image = trim(
            filter_input(INPUT_POST, 'image')
        );
        $this->obj->addImage($image);
    }
    /**
     * Group active directory post element
     *
     * @return void
     */
    public function groupADPost()
    {
        $useAD = isset($_POST['domain']);
        $domain = trim(
            filter_input(
                INPUT_POST,
                'domainname'
            )
        );
        $ou = trim(
            filter_input(
                INPUT_POST,
                'ou'
            )
        );
        $user = trim(
            filter_input(
                INPUT_POST,
                'domainuser'
            )
        );
        $pass = trim(
            filter_input(
                INPUT_POST,
                'domainpassword'
            )
        );
        $this->obj->setAD(
            $useAD,
            $domain,
            $ou,
            $user,
            $pass
        );
    }
    /**
     * Group hosts display.
     *
     * @return void
     */
    public function groupHosts()
    {
        $props = ' method="post" action="'
            . self::makeTabUpdateURL(
                'group-hosts',
                $this->obj->get('id')
            )
            . '" ';

        $buttons = self::makeButton(
            'hosts-add',
            _('Add selected'),
            'btn btn-primary',
            $props
        );
        $buttons .= self::makeButton(
            'hosts-remove',
            _('Remove selected'),
            'btn btn-danger',
            $props
        );

        $this->headerData = [
            _('Host Name'),
            _('Associated')
        ];
        $this->templates = [
            '',
            ''
        ];
        $this->attributes = [
            [],
            []
        ];

        echo '<!-- Hosts -->';
        echo '<div class="box-group" id="hosts">';
        echo '<div class="box box-solid">';
        echo '<div id="updatehosts" class="">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Group Hosts');
        echo '</h4>';
        echo '</div>';
        echo '<div class="box-body">';
        $this->render(12, 'group-hosts-table', $buttons);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Update the group hosts.
     *
     * @return void
     */
    public function groupHostPost()
    {
        if (isset($_POST['updatehosts'])) {
            $hosts = filter_input_array(
                INPUT_POST,
                [
                    'host' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $hosts = $hosts['host'];
            if (count($hosts ?: []) > 0) {
                $this->obj->addHost($hosts);
            }
        }
        if (isset($_POST['hostdel'])) {
            $hosts = filter_input_array(
                INPUT_POST,
                [
                    'hostRemove' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $hosts = $hosts['hostRemove'];
            if (count($hosts ?: []) > 0) {
                $this->obj->removeHost($hosts);
            }
        }
    }
    /**
     * Group printers display.
     *
     * @return void
     */
    public function groupPrinters()
    {
        $printerLevel = (
            filter_input(INPUT_POST, 'level') ?: (
                self::$_common[2] ?
                self::$Host->get('printerLevel') :
                0
            )
        );

        $props = ' method="post" action="'
            . self::makeTabUpdateURL(
                'group-printers',
                $this->obj->get('id')
            )
            . '" ';

        echo '<!-- Printers -->';
        echo '<div class="box-group" id="printers">';

        echo self::makeFormTag(
            'form-horizontal',
            'printer-config-form',
            self::makeTabUpdateURL(
                'group-printers',
                $this->obj->get('id')
            ),
            'post',
            'application/x-www-form-urlencoded',
            true
        );
        echo '<div class="box box-info">';
        echo '<div class="box-header with-border">';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '<h4 class="box-title">';
        echo _('Group Printer Configuration');
        echo '</h4>';
        echo '</div>';
        echo '<div id="printerconf" class="">';
        echo '<div class="box-body">';
        echo '<div class="radio">';
        echo self::makeLabel(
            '',
            'noLevel',
            self::makeInput(
                'printer-nolevel',
                'level',
                '',
                'radio',
                'noLevel',
                '0',
                false,
                false,
                -1,
                -1,
                ($printerLevel == 0 ? 'checked' : '')
            )
            . ' '
            . _('No Printer Management'),
            'data-toggle="tooltip" data-placement="right" title="'
            . _(
                'This setting turns off all FOG Printer Management.'
                . ' Although there are multiple levels already, this '
                . ' is just another level if needed.'
            )
            . '"'
        );
        echo '</div>';
        echo '<div class="radio">';
        echo self::makeLabel(
            '',
            'addlevel',
            self::makeInput(
                'printer-addlevel',
                'level',
                '',
                'radio',
                'addlevel',
                '1',
                false,
                false,
                -1,
                -1,
                ($printerLevel == 1 ? 'checked' : '')
            )
            . ' '
            . _('Add/Remove Managed Printers'),
            'data-toggle="tooltip" data-placement="right" title="'
            . _(
                'This setting only adds and removes '
                . 'printers that are managed by FOG. '
                . 'If the printer exists in printer '
                . 'management but is not assigned to a '
                . 'host, it will remove the printer if '
                . 'it exists on the unassigned host. '
                . 'It will add printers to the host '
                . 'that are assigned.'
            )
            . '"'
        );
        echo '</div>';
        echo '<div class="radio">';
        echo self::makeLabel(
            '',
            'alllevel',
            self::makeInput(
                'printer-alllevel',
                'level',
                '',
                'radio',
                'alllevel',
                '2',
                false,
                false,
                -1,
                -1,
                ($printerLevel == 2 ? 'checked' : '')
            )
            . ' '
            . _('All Printers'),
            'data-toggle="tooltip" data-placement="right" title="'
            . _(
                'This setting will only allow FO GAssigned '
                . 'printers to be added to the host. Any '
                . 'printer that is not assigned will be '
                . 'removed including non-FOG managed printers.'
            )
            . '"'
        );
        echo '</div>';
        echo '</div>';
        echo '<div class="box-footer">';
        echo self::makeButton(
            'printer-config-send',
            _('Update'),
            'btn btn-primary'
        );
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</form>';

        $buttons = self::makeButton(
            'printer-default',
            _('Update default'),
            'btn btn-primary',
            $props
        );
        $buttons .= self::makeButton(
            'printer-add',
            _('Add selected'),
            'btn btn-success',
            $props
        );
        $buttons .= self::makeButton(
            'printer-remove',
            _('Remove selected'),
            'btn btn-danger',
            $props
        );
        $this->headerData = [
            _('Default'),
            _('Printer Alias'),
            _('Printer Type')
        ];
        $this->templates = [
            '',
            '',
            ''
        ];
        $this->attributes = [
            ['width' => '5'],
            [],
            []
        ];
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '<h4 class="box-title">';
        echo _('Update/Remove printers');
        echo '</h4>';
        echo '<div>';
        echo '<p class="help-block">';
        echo _('Changes will automatically be saved');
        echo '</p>';
        echo '</div>';
        echo '</div>';
        echo '<div id="updateprinters" class="">';
        echo '<div class="box-body">';
        $this->render(12, 'group-printers-table', $buttons);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Group Printer Post.
     *
     * @return void
     */
    public function groupPrinterPost()
    {
        if (isset($_POST['levelup'])) {
            $level = filter_input(INPUT_POST, 'level');
            self::getClass('HostManager')
                ->update(
                    ['id' => $this->get('hosts')],
                    '',
                    ['printerLevel' => $level]
                );
        }
        if (isset($_POST['updateprinters'])) {
            $printers = filter_input_array(
                INPUT_POST,
                [
                    'printer' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $printers = $printers['printer'];
            if (count($printers ?: []) > 0) {
                $this->obj->addPrinter($printers);
            }
        }
        if (isset($_POST['defaultsel'])) {
            $this->obj->updateDefault(
                filter_input(
                    INPUT_POST,
                    'default'
                ),
                isset($_POST['default'])
            );
        }
        if (isset($_POST['printdel'])) {
            $printers = filter_input_array(
                INPUT_POST,
                [
                    'printerRemove' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $printers = $printers['printerRemove'];
            if (count($printers ?: []) > 0) {
                $this->obj->removePrinter($printers);
            }
        }
    }
    /**
     * Group snapins.
     *
     * @return void
     */
    public function groupSnapins()
    {
        $props = ' method="post" action="'
            . self::makeTabUpdateURL(
                'group-snapins',
                $this->obj->get('id')
            )
            . '" ';

        $buttons = self::makeButton(
            'snapins-add',
            _('Add selected'),
            'btn btn-primary',
            $props
        );
        $buttons .= self::makeButton(
            'snapins-remove',
            _('Remove selected'),
            'btn btn-danger',
            $props
        );

        $this->headerData = [
            _('Snapin Name'),
            _('Snapin Created')
        ];
        $this->templates = [
            '',
            ''
        ];
        $this->attributes = [
            [],
            []
        ];

        echo '<!-- Snapins -->';
        echo '<div class="box-group" id="snapins">';

        echo '<div class="box box-solid">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Group Snapins');
        echo '</h4>';
        echo '</div>';
        echo '<div id="updatesnapins" class="">';
        echo '<div class="box-body">';
        $this->render(12, 'group-snapins-table', $buttons);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Group snapin post
     *
     * @return void
     */
    public function groupSnapinPost()
    {
        if (isset($_POST['updatesnapins'])) {
            $snapins = filter_input_array(
                INPUT_POST,
                [
                    'snapin' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $snapins = $snapins['snapin'];
            if (count($snapins ?: []) > 0) {
                $this->obj->addSnapin($snapins);
            }
        }
        if (isset($_POST['snapdel'])) {
            $snapins = filter_input_array(
                INPUT_POST,
                [
                    'snapinRemove' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $snapins = $snapins['snapinRemove'];
            if (count($snapins ?: []) > 0) {
                $this->obj->removeSnapin($snapins);
            }
        }
    }
    /**
     * Display's the group service stuff
     *
     * @return void
     */
    public function groupService()
    {
        $props = ' method="post" action="'
            . self::makeTabUpdateURL(
                'group-service',
                $this->obj->get('id')
            )
            . '" ';

        $buttons = self::makeButton(
            'modules-update',
            _('Update'),
            'btn btn-primary',
            $props
        );
        $buttons .= self::makeButton(
            'modules-enable',
            _('Enable All'),
            'btn btn-success',
            $props
        );
        $buttons .= self::makeButton(
            'modules-disable',
            _('Disable All'),
            'btn btn-danger',
            $props
        );

        $this->headerData = [
            _('Module Name'),
            _('Module Association')
        ];
        $this->templates = [
            '',
            ''
        ];
        $this->attributes = [
            [],
            []
        ];

        echo '<!-- Modules/Service Settings -->';
        echo '<div class="box-group" id="modules">';
        echo '<div class="box box-info">';
        echo '<div class="box-header with-border">';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '<h4 class="box-title">';
        echo _('Group module settings');
        echo '</h4>';
        echo '<div>';
        echo '<p class="help-block">';
        echo _('Modules disabled globally cannot be enabled here');
        echo '<br/>';
        echo _('Changes will automatically be saved');
        echo '</p>';
        echo '</div>';
        echo '</div>';
        echo '<div id="updatemodules" class="">';
        echo '<div class="box-body">';
        echo $this->render(12, 'modules-to-update', $buttons);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        // Display Manager Element.
        list(
            $r,
            $x,
            $y
        ) = self::getSubObjectIDs(
            'Service',
            [
                'name' => [
                    'FOG_CLIENT_DISPLAYMANAGER_R',
                    'FOG_CLIENT_DISPLAYMANAGER_X',
                    'FOG_CLIENT_DISPLAYMANAGER_Y'
                ]
            ],
            'value'
        );
        $names = [
            'x' => [
                'width',
                _('Screen Width')
                . '<br/>('
                . _('in pixels')
                . ')'
            ],
            'y' => [
                'height',
                _('Screen Height')
                . '<br/>('
                . _('in pixels')
                . ')'
            ],
            'r' => [
                'refresh',
                _('Screen Refresh Rate')
                . '<br/>('
                . _('in Hz')
                . ')'
            ]
        ];

        $labelClass = 'col-sm-2 control-label';

        foreach ($names as $name => &$get) {
            switch ($name) {
            case 'r':
                $val = $r;
                break;
            case 'x':
                $val = $x;
                break;
            case 'y':
                $val = $y;
                break;
            }
            $fields[
                self::makeLabel(
                    $labelClass,
                    $name,
                    $get[1]
                )
            ] = self::makeInput(
                'form-control',
                $name,
                '',
                'number',
                $name,
                $val
            );
            unset($get);
        }

        $rendered = self::formFields($fields);
        unset($fields);

        echo self::makeFormTag(
            'form-horizontal',
            'group-dispman',
            self::makeTabUpdateURL(
                'group-service',
                $this->obj->get('id')
            ),
            'post',
            'application/x-www-form-urlencoded',
            true
        );
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Display Manager Settings');
        echo '</h4>';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '</div>';
        echo '<div class="box-body">';
        echo $rendered;
        echo self::makeInput(
            '',
            'dispmansend',
            '',
            'hidden',
            '',
            1
        );
        echo '</div>';
        echo '<div class="box-footer">';
        echo self::makeButton(
            'displayman-send',
            _('Update'),
            'btn btn-primary'
        );
        echo '</div>';
        echo '</div>';
        echo '</form>';

        // Auto Log Out
        $tme = filter_input(INPUT_POST, 'tme');
        if (!$tme) {
            $tme = self::getSetting('FOG_CLIENT_AUTOLOGOFF_MIN');
        }
        $fields = [
            self::makeLabel(
                $labelClass,
                'tme',
                _('Auto Logout Time')
                . '<br/>('
                . _('in minutes')
                . ')'
            ) => self::makeInput(
                'form-control',
                'tme',
                '',
                'number',
                'tme',
                $tme
            )
        ];

        $rendered = self::formFields($fields);
        unset($fields);

        echo self::makeFormTag(
            'form-horizontal',
            'group-alo',
            self::makeTabUpdateURL(
                'group-service',
                $this->obj->get('id')
            ),
            'post',
            'application/x-www-form-urlencoded',
            true
        );
        echo '<div class="box box-warning">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Auto Logout Settings');
        echo '</h4>';
        echo '<div>';
        echo '<p class="help-block">';
        echo _('Minimum time limit for Auto Logout to become active is 5 minutes.');
        echo '</p>';
        echo '</div>';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '</div>';
        echo '<div class="box-body">';
        echo $rendered;
        echo self::makeInput(
            '',
            'alosend',
            '',
            'hidden',
            '',
            1
        );
        echo '</div>';
        echo '<div class="box-footer">';
        echo self::makeButton(
            'alo-send',
            _('Update'),
            'btn btn-primary'
        );
        echo '</div>';
        echo '</div>';
        echo '</form>';

        // Hostname changer reboot/domain join reboot forced.
        $enforce = (
            filter_input(INPUT_POST, 'enforce') ?:
            self::$Host->get('enforce')
        );

        $fields = [
            self::makeLabel(
                $labelClass,
                'enforce',
                _('Force Reboot')
            ) => self::makeInput(
                '',
                'enforce',
                '',
                'checkbox',
                'enforce',
                '',
                false,
                false,
                -1,
                -1,
                $enforce
            )
        ];

        $enforcebtn = self::makeButton(
            'enforcebtn',
            _('Update'),
            'btn btn-primary'
        );

        self::$HookManager->processEvent(
            'GROUP_ENFORCE_FIELDS',
            [
                'fields' => &$fields,
                'buttons' => &$enforcebtn,
                'Group' => &$this->obj
            ]
        );
        $rendered = self::formFields($fields);
        unset($fields);

        echo self::makeFormTag(
            'form-horizontal',
            'group-enforce',
            self::makeTabUpdateURL(
                'group-service',
                $this->obj->get('id')
            ),
            'post',
            'application/x-www-form-urlencoded',
            true
        );
        echo '<div class="box box-warning">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Enforce Hostname | AD Join Reboots');
        echo '</h4>';
        echo '<div>';
        echo '<p class="help-block">';
        echo _(
            'This tells the client to force reboots for host name '
            . 'changing and AD Joining.'
        );
        echo '</p>';
        echo '</div>';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '</div>';
        echo '<div class="box-body">';
        echo $rendered;
        echo '</div>';
        echo '<div class="box-footer">';
        echo self::makeInput(
            '',
            'enforcesend',
            '',
            'hidden',
            '',
            '1'
        );
        echo $enforcebtn;
        echo '</div>';
        echo '</div>';
        echo '</form>';
        // End Box Group
        echo '</div>';
    }
    /**
     * Group Service post.
     *
     * @return void
     */
    public function groupServicePost()
    {
        if (isset($_POST['enablemodulessel'])) {
            $enablemodules = filter_input_array(
                INPUT_POST,
                [
                    'enablemodules' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $enablemodules = $enablemodules['enablemodules'];
            $this->obj->addModule($enablemodules);
        }
        if (isset($_POST['disablemodulessel'])) {
            $disablemodules = filter_input_array(
                INPUT_POST,
                [
                    'disablemodules' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $disablemodules = $disablemodules['disablemodules'];
            $this->obj->removeModule($disablemodules);
        }
        if (isset($_POST['dispmansend'])) {
            $x = filter_input(INPUT_POST, 'x');
            $y = filter_input(INPUT_POST, 'y');
            $r = filter_input(INPUT_POST, 'r');
            $this->obj->setDisp($x, $y, $r);
        }
        if (isset($_POST['alosend'])) {
            $tme = (int)filter_input(INPUT_POST, 'tme');
            if (!(is_numeric($tme) && $tm > 4)) {
                $tme = 0;
            }
            $this->obj->setAlo($tme);
        }
        if (isset($_POST['enforcesend'])) {
            $enforce = isset($_POST['enforce']);
            self::getClass('HostManager')->update(
                ['id' => $this->obj->get('hosts')],
                '',
                ['enforce' => $enforce]
            );
        }
    }
    /**
     * Display the group PM stuff.
     *
     * @return void
     */
    public function groupPowermanagement()
    {
        echo '<!-- Power Management -->';
        echo $this->newPMDisplay();
    }
    /**
     * Modify the power management stuff.
     *
     * @return void
     */
    public function groupPowermanagementPost()
    {
        $hostIDs = (array)$this->obj->get('hosts');
        if (isset($_POST['pmadd'])) {
            $onDemand = (int)isset($_POST['onDemand']);
            $min = filter_input(INPUT_POST, 'scheduleCronMin');
            $hour = filter_input(INPUT_POST, 'scheduleCronHour');
            $dom = filter_input(INPUT_POST, 'scheduleCronDOM');
            $month = filter_input(INPUT_POST, 'scheduleCronMonth');
            $dow = filter_input(INPUT_POST, 'scheduleCronDOW');
            $action = filter_input(INPUT_POST, 'action');
            if (!$action) {
                throw new Exception(_('You must select an action to perform'));
            }
            $items = [];
            if ($onDemand && $action === 'wol') {
                $this->obj->wakeOnLAN();
                return;
            }
            foreach ((array)$hostIDs as &$hostID) {
                $items[] = [
                    $hostID,
                    $min,
                    $hour,
                    $dom,
                    $month,
                    $dow,
                    $onDemand,
                    $action
                ];
                unset($hostID);
            }
            $fields = [
                'hostID',
                'min',
                'hour',
                'dom',
                'month',
                'dow',
                'onDemand',
                'action'
            ];
            if (count($items) > 0) {
                self::getClass('PowerManagementManager')
                    ->insertBatch($fields, $items);
            }
        }
        if (isset($_POST['pmdelete'])) {
            self::getClass('PowerManagementManager')->destroy(
                ['hostID' => $hostIDs]
            );
        }
    }
    /**
     * The group edit display method
     *
     * @return void
     */
    public function edit()
    {
        list(
            $imageIDs,
            $groupKey,
            $printerLevel,
            $aduse,
            $enforcetest,
            $adDomain,
            $adOU,
            $adUser,
            $adPass,
            $biosExit,
            $efiExit
        ) = self::$_common;
        self::$HookManager->processEvent(
            'GROUP_COMMON_HOOK',
            ['common' => &self::$_common]
        );
        $hostids = $this->obj->get('hosts');
        self::$Host = new Host(@max($hostids));
        $useAD = (
            $aduse ?
            self::$Host->get('useAD') :
            ''
        );
        $ADDomain = (
            $adDomain ?
            self::$Host->get('ADDomain') :
            ''
        );
        $ADOU = (
            $adOU ?
            self::$Host->get('ADOU') :
            ''
        );
        $ADUser = (
            $adUser ?
            self::$Host->get('ADUser') :
            ''
        );
        $adPass = (
            $adPass ?
            self::$Host->get('ADPass') :
            ''
        );
        $ADPass = self::$Host->get('ADPass');

        $this->title = sprintf(
            '%s: %s',
            _('Edit'),
            $this->obj->get('name')
        );

        $tabData = [];

        // General
        $tabData[] = [
            'name' => _('General'),
            'id' => 'group-general',
            'generator' => function () {
                $this->groupGeneral();
            }
        ];

        // Image
        $tabData[] = [
            'name' => _('Image'),
            'id' => 'group-image',
            'generator' => function () {
                $this->groupImage();
            }
        ];

        // Tasks
        $tabData[] = [
            'name' => _('Tasks'),
            'id' => 'group-tasks',
            'generator' => function () {
                $this->basictasksOptions();
            }
        ];

        // Associations
        $tabData[] = [
            'tabs' => [
                'name' => _('Associations'),
                'tabData' => [
                    [
                        'name' => _('Hosts'),
                        'id' => 'group-hosts',
                        'generator' => function () {
                            $this->groupHosts();
                        }
                    ],
                    [
                        'name' => _('Printers'),
                        'id' => 'group-printers',
                        'generator' => function () {
                            $this->groupPrinters();
                        }
                    ],
                    [
                        'name' => _('Snapins'),
                        'id' => 'group-snapins',
                        'generator' => function () {
                            $this->groupSnapins();
                        }
                    ]
                ]
            ]
        ];

        // FOG Client settings.
        $tabData[] = [
            'tabs' => [
                'name' => _('Service Settings'),
                'tabData' => [
                    [
                        'name' => _('Client Module Settings'),
                        'id' => 'group-service',
                        'generator' => function () {
                            $this->groupService();
                        }
                    ],
                    [
                        'name' => _('Active Directory'),
                        'id' => 'group-active-directory',
                        'generator' => function () {
                            $this->adFieldsToDisplay(
                                $useAD,
                                $ADDomain,
                                $ADOU,
                                $ADUser,
                                $ADPass
                            );
                        }
                    ],
                    [
                        'name' => _('Power Management'),
                        'id' => 'group-powermanagement',
                        'generator' => function () {
                            $this->groupPowermanagement();
                        }
                    ]
                ]
            ]
        ];

        // Inventory
        $tabData[] = [
            'name' => _('Inventory'),
            'id' => 'group-inventory',
            'generator' => function () {
                $this->groupInventory();
            }
        ];

        echo self::tabFields($tabData, $this->obj);
    }
    /**
     * Display inventory page, separated as groups can contain
     * a lot of information
     *
     * @return void
     */
    public function groupInventory()
    {
        echo 'TODO: Make Functional';
    }
    /**
     * Submit the edit function.
     *
     * @return void
     */
    public function editPost()
    {
        header('Content-type: appication/json');
        self::$HookManager->processEvent(
            'GROUP_EDIT_POST',
            ['Group' => &$this->obj]
        );

        $serverFault = false;
        try {
            global $tab;
            switch ($tab) {
            case 'group-general':
                $this->groupGeneralPost();
                break;
            case 'group-image':
                $this->groupImagePost();
                break;
            case 'group-active-directory':
                $this->groupADPost();
                break;
            case 'group-hosts':
                $this->groupHostPost();
                break;
            case 'group-printers':
                $this->groupPrinterPost();
                break;
            case 'group-snapins':
                $this->groupSnapinPost();
                break;
            case 'group-service':
                $this->groupServicePost();
                break;
            case 'group-powermanagement':
                $this->groupPowermanagementPost();
                break;
            }
            if (!$this->obj->save()) {
                $serverFault = true;
                throw new Exception(_('Group update failed!'));
            }
            $code = HTTPResponseCodes::HTTP_ACCEPTED;
            $hook = 'GROUP_EDIT_SUCCESS';
            $msg = json_encode(
                [
                    'msg' => _('Group updated!'),
                    'title' => _('Group Update Success')
                ]
            );
        } catch (Exception $e) {
            $code = (
                $serverFault ?
                HTTPResponseCodes::HTTP_INTERNAL_SERVER_ERROR :
                HTTPResponseCodes::HTTP_BAD_REQUEST
            );
            $hook = 'GROUP_EDIT_FAIL';
            $msg = json_encode(
                [
                    'error' => $e->getMessage(),
                    'title' => _('Group Update Fail')
                ]
            );
        }
        self::$HookManager->processEvent(
            $hook,
            [
                'Group' => &$this->obj,
                'hook' => &$hook,
                'code' => &$code,
                'msg' => &$msg,
                'serverFault' => &$serverFault
            ]
        );
        http_response_code($code);
        echo $msg;
        exit;
    }
    /**
     * Presents the hosts list table.
     *
     * @return void
     */
    public function getHostsList()
    {
        header('Content-type: application/json');
        parse_str(
            file_get_contents('php://input'),
            $pass_vars
        );

        // Workable queries
        $hostsSqlStr = "SELECT `%s`,"
            . "IF(`gmGroupID` = '"
            . $this->obj->get('id')
            . "','associated','dissociated') AS `gmGroupID`
            FROM `%s`
            LEFT OUTER JOIN `groupMembers`
            ON `hosts`.`hostID` = `groupMembers`.`gmHostID`
            AND `groupMembers`.`gmGroupID` = '"
            . $this->obj->get('id')
            . "'
            %s
            %s
            %s";
        $hostsFilterStr = "SELECT COUNT(`%s`)
            FROM `%s`
            LEFT OUTER JOIN `groupMembers`
            ON `hosts`.`hostID` = `groupMembers`.`gmHostID`
            AND `groupMembers`.`gmGroupID` = '"
            . $this->obj->get('id')
            . "'
            %s";
        $hostsTotalStr = "SELECT COUNT(`%s`)
            FROM `%s`";
        foreach (self::getClass('HostManager')
            ->getColumns() as $common => &$real
        ) {
            $columns[] = [
                'db' => $real,
                'dt' => $common
            ];
            unset($real);
        }
        $columns[] = [
            'db' => 'gmGroupID',
            'dt' => 'association'
        ];
        echo json_encode(
            FOGManagerController::complex(
                $pass_vars,
                'hosts',
                'hostID',
                $columns,
                $hostsSqlStr,
                $hostsFilterStr,
                $hostsTotalStr,
                $where
            )
        );
        exit;
    }
    /**
     * Presents the printers list table.
     *
     * @return void
     */
    public function getPrintersList()
    {
        header('Content-type: application/json');
        parse_str(
            file_get_contents('php://input'),
            $pass_vars
        );

        $obj = self::getClass('PrinterManager');

        // Workable queries
        $printersTable = $obj->getTable();
        $printersSqlStr = $obj->getQueryStr();
        $printersFilterStr = $obj->getFilterStr();
        $printersTotalStr = $obj->getTotalStr();

        foreach ($obj->getColumns() as $common => &$real) {
            if ('id' == $common) {
                $tableID = $real;
            }
            $columns[] = [
                'db' => $real,
                'dt' => $common
            ];
            unset($real);
        }
        echo json_encode(
            FOGManagerController::complex(
                $pass_vars,
                $printersTable,
                $tableID,
                $columns,
                $printersSqlStr,
                $printersFilterStr,
                $printersTotalStr
            )
        );
        exit;
    }
    /**
     * Presents the snapins list table.
     *
     * @return void
     */
    public function getSnapinsList()
    {
        header('Content-type: application/json');
        parse_str(
            file_get_contents('php://input'),
            $pass_vars
        );

        $obj = self::getClass('SnapinManager');

        // Workable queries
        $snapinsTable = $obj->getTable();
        $snapinsSqlStr = $obj->getQueryStr();
        $snapinsFilterStr = $obj->getFilterStr();
        $snapinsTotalStr = $obj->getTotalStr();

        foreach (self::getClass('SnapinManager')
            ->getColumns() as $common => &$real
        ) {
            if ('id' == $common) {
                $tableID = $real;
            }
            $columns[] = [
                'db' => $real,
                'dt' => $common
            ];
            unset($real);
        }
        echo json_encode(
            FOGManagerController::complex(
                $pass_vars,
                $snapinsTable,
                $tableID,
                $columns,
                $snapinsSqlStr,
                $snapinsFilterStr,
                $snapinsTotalStr
            )
        );
        exit;
    }
    /**
     * Returns the module list as well as the associated
     * for the group being edited.
     *
     * @return void
     */
    public function getModulesList()
    {
        header('Content-type: application/json');
        parse_str(
            file_get_contents('php://input'),
            $pass_vars
        );

        $moduleName = self::getGlobalModuleStatus();
        $keys = [];
        foreach ((array)$moduleName as $short_name => $bool) {
            if ($bool) {
                $keys[] = $short_name;
            }
        }
        $notWhere = [
            'clientupdater',
            'dircleanup',
            'greenfog',
            'usercleanup'
        ];

        $where = "`modules`.`short_name` "
            . "NOT IN ('"
            . implode("','", $notWhere)
            . "') AND `modules`.`short_name` IN ('"
            . implode("','", $keys)
            . "')";

        $obj = self::getClass('ModuleManager');

        // Workable queries
        $modulesTable = $obj->getTable();
        $modulesSqlStr = $obj->getQueryStr();
        $modulesFilterStr = $obj->getFilterStr();
        $modulesTotalStr = $obj->getTotalStr() .
            "WHERE `modules`.`short_name` "
            . "NOT IN ('"
            . implode("','", $notWhere)
            . "')";

        foreach ($obj->getColumns() as $common => &$real) {
            if ('id' == $common) {
                $tableID = $real;
            }
            $columns[] = [
                'db' => $real,
                'dt' => $common
            ];
            unset($real);
        }
        echo json_encode(
            FOGManagerController::complex(
                $pass_vars,
                $modulesTable,
                $tableID,
                $columns,
                $modulesSqlStr,
                $modulesFilterStr,
                $modulesTotalStr
            )
        );
        exit;
    }
    /**
     * Present the export information.
     *
     * @return void
     */
    public function export()
    {
        // The data to use for building our table.
        $this->headerData = [];
        $this->templates = [];
        $this->attributes = [];

        $obj = self::getClass('GroupManager');

        foreach ($obj->getColumns() as $common => &$real) {
            if ('id' == $common) {
                continue;
            }
            array_push($this->headerData, $common);
            array_push($this->templates, '');
            array_push($this->attributes, []);
            unset($real);
        }

        $this->title = _('Export Groups');

        echo '<div class="box box-solid">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Export Groups');
        echo '</h4>';
        echo '<p class="help-block">';
        echo _('Use the selector to choose how many items you want exported.');
        echo '</p>';
        echo '</div>';
        echo '<div class="box-body">';
        echo '<p class="help-block">';
        echo _(
            'When you click on the item you want to export, it can only select '
            . 'what is currently viewable on the screen. This includes searched '
            . 'and the current page. Please use the selector to choose the amount '
            . 'of items you would like to export.'
        );
        echo '</p>';
        $this->render(12, 'group-export-table');
        echo '</div>';
        echo '</div>';
    }
    /**
     * Present the export list.
     *
     * @return void
     */
    public function getExportList()
    {
        header('Content-type: application/json');
        $obj = self::getClass('GroupManager');
        $table = $obj->getTable();
        $sqlstr = $obj->getQueryStr();
        $filterstr = $obj->getFilterStr();
        $totalstr = $obj->getTotalStr();
        $dbcolumns = $obj->getColumns();
        $pass_vars = $columns = [];
        parse_str(
            file_get_contents('php://input'),
            $pass_vars
        );
        // Setup our columns for the CSVn.
        // Automatically removes the id column.
        foreach ($dbcolumns as $common => &$real) {
            if ('id' == $common) {
                $tableID = $real;
                continue;
            }
            $columns[] = [
                'db' => $real,
                'dt' => $common
            ];
            unset($real);
        }
        self::$HookManager->processEvent(
            'GROUP_EXPORT_ITEMS',
            [
                'table' => &$table,
                'sqlstr' => &$sqlstr,
                'filterstr' => &$filterstr,
                'totalstr' => &$totalstr,
                'columns' => &$columns
            ]
        );
        echo json_encode(
            FOGManagerController::simple(
                $pass_vars,
                $table,
                $tableID,
                $columns,
                $sqlstr,
                $filterstr,
                $totalstr
            )
        );
        exit;
    }
    /**
     * Tasking for this group.
     *
     * @return void
     */
    public function deploy()
    {
        global $type;
        global $id;

        try {
            if (!is_numeric($type) || $type < 1) {
                $type = 1;
            }

            $TaskType = new TaskType($type);

            $this->title = $TaskType->get('name')
                . ' '
                . $this->obj->get('name');

            $imagingTypes = $TaskType->isImagingTask();
            $iscapturetask = $TaskType->isCapture();
            $issnapintask = $TaskType->isSnapinTasking();
            $isinitneeded = $TaskType->isInitNeededTasking();
            $isdebug = $TaskType->isDebug();
            $hosts = $this->obj->get('hosts');
            //$image = $this->obj->getImage();

            if (!$TaskType->isValid()) {
                throw new Exception(_('Task type is invalid'));
            }
            if (count($hosts ?: []) < 1) {
                throw new Exception(_('There are no hosts to task'));
            }
            if ($iscapturetask) {
                throw new Exception(_('Groups cannot create capture tasks'));
            }

            $labelClass = 'col-sm-2 control-label';

            $fields = [];

            if ($issnapintask
                && TaskType::SINGLE_SNAPIN == $type
            ) {
                $snapinSelector = self::getClass('SnapinManager')
                    ->buildSelectBox('', 'snapin');
                $fields[
                    self::makeLabel(
                        $labelClass,
                        'snapin',
                        _('Select Snapin to run')
                    )
                ] = $snapinSelector;
            } else if (TaskType::PASSWORD_RESET == $type) {
                $fields [
                    self::makeLabel(
                        $labelClass,
                        'account',
                        _('Account Name')
                    )
                ] = self::makeInput(
                    'form-control',
                    'account',
                    'Administrator',
                    'text',
                    'account',
                    '',
                    true
                );
            }
            if ($isinitneeded
                && !$isdebug
            ) {
                $shutdownchecked = self::getSetting(
                    'FOG_TASKING_ADV_SHUTDOWN_ENABLED'
                ) ? ' checked' : '';
                $fields = self::fastmerge(
                    $fields,
                    [
                        '<div class="hideFromDebug">'
                        . self::makeLabel(
                            $labelClass,
                            'shutdown',
                            _('Shutdown when complete')
                        ) => self::makeInput(
                            '',
                            'shutdown',
                            '',
                            'checkbox',
                            'shutdown',
                            '',
                            false,
                            false,
                            -1,
                            -1,
                            $shutdownchecked
                        )
                        . '</div>'
                    ]
                );
            }
            if (TaskType::WAKE_UP != $type) {
                $wolchecked = self::getSetting(
                    'FOG_TASKING_ADV_WOL_ENABLED'
                ) ? ' checked' : '';
                $fields = self::fastmerge(
                    $fields,
                    [
                        self::makeLabel(
                            $labelClass,
                            'wol',
                            _('Wake Up')
                        ) => self::makeInput(
                            '',
                            'wol',
                            '',
                            'checkbox',
                            'wol',
                            '',
                            false,
                            false,
                            -1,
                            -1,
                            $wolchecked
                        )
                    ]
                );
            }
            if (TaskType::PASSWORD_RESET != $type
                && !$isdebug
                && $isinitneeded
            ) {
                $debugchecked = self::getSetting(
                    'FOG_TASKING_ADV_DEBUG_ENABLED'
                ) ? ' checked' : '';
                $fields = self::fastmerge(
                    $fields,
                    [
                        self::makeLabel(
                            $labelClass,
                            'checkdebug',
                            _('Debug Task')
                        ) => self::makeInput(
                            '',
                            'isDebugTask',
                            '',
                            'checkbox',
                            'checkdebug',
                            '',
                            false,
                            false,
                            -1,
                            -1,
                            $debugchecked
                        )
                    ]
                );
            }
            $fields = self::fastmerge(
                $fields,
                [
                    self::makeLabel(
                        $labelClass,
                        'instant',
                        _('Schedule Immediately')
                    ) => self::makeInput(
                        'instant',
                        'scheduleType',
                        '',
                        'radio',
                        'instant',
                        'instant',
                        false,
                        false,
                        -1,
                        -1,
                        ' checked'
                    )
                ]
            );
            if (!$isdebug
                && TaskType::PASSWORD_RESET != $type
            ) {
                $fields = self::fastmerge(
                    $fields,
                    [
                        '<div class="hideFromDebug">'
                        . self::makeLabel(
                            $labelClass,
                            'delayed',
                            _('Schedule Later')
                        ) => self::makeInput(
                            'delayed',
                            'scheduleType',
                            '',
                            'radio',
                            'delayed',
                            'single'
                        )
                        . '</div>',
                        '<div class="delayedinput hidden">'
                        . self::makeLabel(
                            $labelClass,
                            'delayedinput',
                            _('Start Time')
                        ) => self::makeInput(
                            'form-control',
                            'scheduleSingleTime',
                            self::niceDate()->format('Y-m-d H:i:s'),
                            'text',
                            'delayedinput',
                            ''
                        )
                        . '</div>',
                        '<div class="hideFromDebug">'
                        . self::makeLabel(
                            $labelClass,
                            'cron',
                            _('Schedule Crontab Style')
                        ) => self::makeInput(
                            'croninput',
                            'scheduleType',
                            '',
                            'radio',
                            'cron',
                            'cron'
                        )
                        . '</div>',
                        '<div class="croninput hidden">'
                        . self::makeLabel(
                            $labelClass,
                            'specialCrons',
                            _('Special Crons')
                        ) => FOGCron::buildSpecialCron('specialCrons')
                        . '</div>',
                        '<div class="croninput hidden">'
                        . self::makeLabel(
                            $labelClass,
                            '',
                            _('Cron Entry')
                        ) => self::makeInput(
                            'col-sm-2 croninput cronmin hidden',
                            'scheduleCronMin',
                            _('min'),
                            'text',
                            'cronMin'
                        )
                        . self::makeInput(
                            'col-sm-2 croninput cronhour hidden',
                            'scheduleCronHour',
                            _('hour'),
                            'text',
                            'cronHour'
                        )
                        . self::makeInput(
                            'col-sm-2 croninput crondom hidden',
                            'scheduleCronDOM',
                            _('day'),
                            'text',
                            'cronDom'
                        )
                        . self::makeInput(
                            'col-sm-2 croninput cronmonth hidden',
                            'scheduleCronMonth',
                            _('month'),
                            'text',
                            'cronMonth'
                        )
                        . self::makeInput(
                            'col-sm-2 croninput crondow hidden',
                            'scheduleCronDOW',
                            _('weekday'),
                            'text',
                            'cronDow'
                        )
                        . '</div>'
                    ]
                );
            }

            $buttons = self::makeButton(
                'tasking-send',
                _('Create'),
                'btn btn-primary'
            );

            self::$HookManager->processEvent(
                'GROUP_CREATE_TASKING',
                [
                    'fields' => &$fields,
                    'buttons' => &$buttons,
                    'Host' => &$this->obj
                ]
            );
            $rendered = self::formFields($fields);
            unset($fields);

            echo self::makeFormTag(
                'form-horizontal',
                'group-deploy-form',
                $this->formAction,
                'post',
                'application/x-www-form-url-encoded',
                true
            );
            echo '<div class="box box-solid">';
            echo '<div class="box-header with-border">';
            echo '<h4 class="box-title">';
            echo $this->title;
            echo '</h4>';
            echo '</div>';
            echo '<div class="box-body">';
            echo $rendered;
            echo '</div>';
            echo '<div class="box-footer">';
            echo $buttons;
            echo '</div>';
            echo '</div>';
            echo '</form>';
        } catch (Exception $e) {
            echo self::displayAlert(
                'Tasking Cannot Occur',
                $e->getMessage(),
                'warning',
                false
            );
        }
    }
}