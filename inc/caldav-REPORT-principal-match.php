<?php

$request->NeedPrivilege(array('DAV::read','DAV::read-current-user-privilege-set','DAV::read-acl'));

if ( $request->depth > 0 ) {
  $request->DoResponse( 400, 'The principal-match REPORT is only defined for Depth "0".' );
}

/**
 * Determine which principal we're looking for
 */
$match = $xmltree->GetPath('/DAV::principal-match/DAV::self');
if ( count ( $match ) == 0 ) {
  $match = $xmltree->GetPath('/DAV::principal-match/DAV::principal-property');
  $match_self = false;
}
else {
  $match_self = true;
}
if ( count ( $match ) == 0 ) {
  $request->DoResponse( 400, 'Badly formed principal-match REPORT request.' );
}

$target = new DAVResource($request->path);

$where = '';
if ( $match_self ) {
  // Technically we should restrict to finding the principal somewhere *below* the
  // request path in the hierarchy, but we'll quietly ignore that because it's
  // unlikely that anything would actually be wanting that behaviour.
  $where .= 'username = :username';
  $params = array(':username' => $session->username );
}
else {
  $where = 'dav_name = :dav_name';
  $params = array(':dav_name'=>'/'.$request->principal->GetProperty('username').'/');
}
$sql = "SELECT * FROM dav_principal WHERE $where ORDER BY principal_id LIMIT 100";

if ( $target->IsPrincipal() ) {
  // The request path is more specific, so ALTERNATIVELY,
  // we find this principal's resources (collections) instead
  $sql = "SELECT * FROM collection WHERE user_no = :user_no";
  $params = array(':user_no' => $target->user_no() );
}

$qry = new AwlQuery($sql, $params);


/**
 * Build the array of properties to include in the report output
 */
$get_props = $xmltree->GetPath('/DAV::principal-match/DAV::prop/*');
$properties = array();
foreach( $get_props AS $k1 => $v1 ) {
  $properties[] = $v1->GetNSTag();
}

$responses = array();
if ( $qry->Exec("REPORT",__LINE__,__FILE__) && $qry->rows() > 0 ) {
  while( $row = $qry->Fetch() ) {
    $principal = new DAVResource($row);
    $responses[] = $principal->RenderAsXML( $properties, $reply );
  }
}

$multistatus = new XMLElement( "multistatus", $responses, $reply->GetXmlNsArray() );

$request->XMLResponse( 207, $multistatus );
