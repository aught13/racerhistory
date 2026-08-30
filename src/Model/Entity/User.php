<?php
declare(strict_types=1);

namespace App\Model\Entity;

use ArrayAccess;
use Authentication\IdentityInterface as AuthenticationIdentity;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * Represents a user in the system with authentication credentials and profile information.
 * Implements Authentication identity interface.
 *
 * Note: Authorization is handled via the Authorization plugin's IdentityDecorator,
 * not by implementing AuthorizationIdentityInterface directly on this entity.
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $role
 * @property int|null $role_id
 * @property string|null $display_name
 * @property string|null $bio
 * @property int|null $profile_image_id
 * @property string|null $website_url
 * @property string|array|null $social_links
 * @property string $status
 * @property bool $active
 * @property bool $is_superuser
 * @property string|null $token
 * @property \Cake\I18n\DateTime|null $token_expires
 * @property string|null $api_token
 * @property \Cake\I18n\DateTime|null $activation_date
 * @property \Cake\I18n\DateTime|null $tos_date
 * @property \Cake\I18n\DateTime|null $last_login
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property string|null $secret
 * @property bool|null $secret_verified
 * @property string|null $additional_data
 * @property \App\Model\Entity\Role|null $role_record
 */
class User extends Entity implements AuthenticationIdentity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<string>
     */
    protected array $_hidden = [
        'password',
    ];

    /**
     * Authentication: Get identifier for this identity
     *
     * @return array<mixed>|string|int|null
     */
    public function getIdentifier(): array|string|int|null
    {
        return $this->id;
    }

    /**
     * Authentication: Get original data
     *
     * Returns this entity itself, as it implements ArrayAccess.
     *
     * @return \ArrayAccess<string, mixed>|array<string, mixed>
     */
    public function getOriginalData(): array|ArrayAccess
    {
        return $this;
    }
}
