<?php
namespace inidiff;

$locale_dir = "examples/";
$locale_files = glob("$locale_dir*.ini");

$locales = array();
foreach ($locale_files as $locale_file){
  $locale_name = substr($locale_file, strlen($locale_dir), -4);
  $locales[$locale_name] = parse_ini_file($locale_file, true);
}

$all_keys = new \stdClass();
$all_keys->root = array();
$all_keys->sections = array();

$locale_keys = array();

foreach ($locales as $name =>$locale) {
  $current = new \stdClass();
  $current->root = array();
  $current->sections = array();
  foreach($locale as $key=>$value){
    if(is_array($value)){
      $keys = array_keys($value);
      $current->sections[$key] = $keys;
      if(array_key_exists($key, $all_keys->sections)){
        $keys = array_unique(array_merge($all_keys->sections[$key], $keys));
      }
      $all_keys->sections[$key] = $keys;
    }else{
      $all_keys->root[] = $key;
      $current->root[] = $key;
    }
    $locale_keys[$name] = $current;
  }
  # Remove duplicates
  $all_keys->root = array_unique($all_keys->root, SORT_REGULAR);
  $all_keys->sections = array_unique($all_keys->sections, SORT_REGULAR);

}

function print_diff($all, $current, $name){
  if (!is_array($current)) {
    $current =array();
  }
  $diff = array_diff($all, $current);
  if(sizeof($diff) == 0){
    $html = '<p class="ok">No keys missing in "' . $name . '"!</p>';
    return array($html, true);
  }else{
    $html = '<p class="warn">Missing keys in "' . $name . '":</p><ul>';
    foreach($diff as $key){
      $html .= "<li>$key</li>";
    }
    $html .= "</ul>";
    return array($html, false);
  }
}

$output = "";
foreach ($locales as $name =>$locale) {
  $ok = true;
  $html = "";
  $res = print_diff($all_keys->root, $locale_keys[$name]->root, "root");
  $html .= $res[0];
  if(!$res[1]) {$ok = false;}

  foreach($all_keys->sections as $key => $all){
    
    $res = print_diff($all, $locale_keys[$name]->sections[$key], $key);
    $html .= $res[0];
    if(!$res[1]) {$ok = false;}
  }
  $class = "incomplete";
  if($ok){
    $class = "complete";
  }
  $output .= "<h1 class=\"$class\">Locale: '$name'</h1>";
  $output .= $html;
}

$template = file_get_contents("ini_diff.html");
echo str_replace('{{CONTENT}}', $output, $template);

?>
