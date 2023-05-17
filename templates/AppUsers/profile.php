<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AppUser $user
 */

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('app_users', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('app_users', 'Change Password'),
                ['action' => 'changePassword'],
                ['class' => 'side-nav-item']
            ); ?>
            <?= $this->AuthLink->link(
                __d('app_users', 'User Settings'),
                ['action' => 'userSettings'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="users view content">
            <h3><?= $this->Html->image(
                empty($user->avatar) ? $avatarPlaceholder : $user->avatar,
                ['width' => '180', 'height' => '180'],
            ); ?></h3>
            <h3>
                <?=
                $this->Html->tag(
                    'span',
                    __d('app_users', '{0} {1}', $user->first_name, $user->last_name),
                    ['class' => 'full_name']
                )
                ?>
            </h3>
            <div class="row">
                <div class="large-6 columns strings">
                    <h6 class="subheader"><?= __d('app_users', 'Username') ?></h6>
                    <p><?= h($user->username) ?></p>
                    <h6 class="subheader"><?= __d('app_users', 'Email') ?></h6>
                    <p><?= h($user->email) ?></p>
                    <h6 class="subheader"><?= __d('app_users', 'Role') ?></h6>
                    <p><?= h($user->getRoleName()) ?></p>
                    <?= $this->User->socialConnectLinkList($user->social_accounts) ?>
                    <?php
                    if (!empty($user->social_accounts)) :
                        ?>
                        <h6 class="subheader"><?= __d('app_users', 'Social Accounts') ?></h6>
                        <table cellpadding="0" cellspacing="0">
                            <thead>
                            <tr>
                                <th><?= __d('app_users', 'Avatar'); ?></th>
                                <th><?= __d('app_users', 'Provider'); ?></th>
                                <th><?= __d('app_users', 'Link'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach ($user->social_accounts as $socialAccount) :
                                $escapedUsername = h($socialAccount->username);
                                $linkText = empty($escapedUsername) ?
                                    __d('app_users', 'Link to {0}', h($socialAccount->provider)) :
                                    h($socialAccount->username)
                                ?>
                                <tr>
                                    <td><?=
                                        $this->Html->image(
                                            $socialAccount->avatar,
                                            ['width' => '90', 'height' => '90']
                                        ) ?>
                                    </td>
                                    <td><?= h($socialAccount->provider) ?></td>
                                    <td><?=
                                        $socialAccount->link && $socialAccount->link != '#' ? $this->Html->link(
                                            $linkText,
                                            $socialAccount->link,
                                            ['target' => '_blank']
                                        ) : '-' ?></td>
                                </tr>
                                <?php
                            endforeach;
                            ?>
                            </tbody>
                        </table>
                        <?php
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
