<?php
/**
 * @package    API_Plugins
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
defined('_JEXEC') or die('Restricted access');

/** To build get album simple schema
 *
 * @since  1.8.8
 */
class GetalbumsSimpleSchema
{
	public $id;

	public $cover_id;

	public $uid;

	public $type;

	public $title;

	public $caption;

	public $created;

	public $assigned_date;

	public $cover_featured;

	public $cover_large;

	public $cover_square;

	public $cover_thumbnail;

	public $count;

	public $likes;

	public $total;

	public $comments;

	public $comment_element;

	public $commentcount;

	public $isowner;

	public $share_url;
}
