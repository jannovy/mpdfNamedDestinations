# mpdfNamedDestinations
extension for mPDF working with named destinations

what you looking for is this small snippet 
```
// catch "go.to" and override annotation with link to remote document
if(strpos($pl[4], 'go.to')!==false){
	$target = explode('go.to:', $pl[4]);
	$target = $target[count($target)-1];
	$annot = '<</Type /Annot /Subtype /Link /Rect ['.$rect.'] /Border [0 0 0] /A <</S /GoTo /D ('.$target.')>>>>';
	$this->_out($annot);
	$this->_out('endobj');
	// finish loop
	continue;
}
```

basic usage:
```
$mpdf->WriteHTML('<p><a href="go.to:doc-A">go to document A</a></p>');
```
