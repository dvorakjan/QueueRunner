<?php
/**
 * @author Vojta Biberle
 * @since 12.1.15
 */

namespace QueueManager;

interface IMessage {
    public static function create($data);
    public function toArray();
}