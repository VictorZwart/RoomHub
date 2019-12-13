<?php
/* connect here */

/* define db models here */

use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\ManyHasOne;
use Nextras\Orm\Relationships\OneHasMany;
use Nextras\Orm\Relationships\OneHasOne;



class Listing extends Entity {
}

class User extends Entity {
}


/**
 * @property int $room_id    {primary}
 * @property string $description
 * @property float $price
 * @property string $size
 * @property string $type
 * @property
 * @property User $owner_id {m:1 User}
 */
class Room extends Entity {
}