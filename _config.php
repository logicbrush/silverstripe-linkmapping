<?php
/**
 * @package silverstripe-linkmapping
 */

SS_Object::add_extension("RequestHandler", "LinkMappingExtension");
SS_Object::add_extension("ContentController", "LinkMappingExtension");
SS_Object::add_extension("ModelAsController", "LinkMappingExtension");
