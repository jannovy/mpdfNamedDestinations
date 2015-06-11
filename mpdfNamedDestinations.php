<?php
class mpdfNamedDestinations extends mPDF
{

	function _putannots() {	// mPDF 5.7.2
		$filter=($this->compress) ? '/Filter /FlateDecode ' : '';
		$nb=$this->page;
		for($n=1;$n<=$nb;$n++)
		{
			$annotobjs = array();
			if(isset($this->PageLinks[$n]) || isset($this->PageAnnots[$n]) || count($this->form->forms) > 0 ) {
				$wPt=$this->pageDim[$n]['w']*_MPDFK;
				$hPt=$this->pageDim[$n]['h']*_MPDFK;

				//Links
				if(isset($this->PageLinks[$n])) {
				   foreach($this->PageLinks[$n] as $key => $pl) {
					$this->_newobj();
					$annot='';
					$rect=sprintf('%.3F %.3F %.3F %.3F',$pl[0],$pl[1],$pl[0]+$pl[2],$pl[1]-$pl[3]);
					$annot .= '<</Type /Annot /Subtype /Link /Rect ['.$rect.']';
					$annot .= ' /Contents '.$this->_UTF16BEtextstring($pl[4]);
					$annot .= ' /NM '.$this->_textstring(sprintf('%04u-%04u', $n, $key));
					$annot .= ' /M '.$this->_textstring('D:'.date('YmdHis'));
					$annot .= ' /Border [0 0 0]';
					// Use this (instead of /Border) to specify border around link
			//		$annot .= ' /BS <</W 1';	// Width on points; 0 = no line
			//		$annot .= ' /S /D';		// style - [S]olid, [D]ashed, [B]eveled, [I]nset, [U]nderline
			//		$annot .= ' /D [3 2]';		// Dash array - if dashed
			//		$annot .= ' >>';
			//		$annot .= ' /C [1 0 0]';	// Color RGB

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

					if ($this->PDFA || $this->PDFX) { $annot .= ' /F 28'; }
					if (strpos($pl[4],'@')===0) {
						$p=substr($pl[4],1);
						//	$h=isset($this->OrientationChanges[$p]) ? $wPt : $hPt;
						$htarg=$this->pageDim[$p]['h']*_MPDFK;
						$annot.=sprintf(' /Dest [%d 0 R /XYZ 0 %.3F null]>>',1+2*$p,$htarg);
					}
					else if(is_string($pl[4])) {
						$annot .= ' /A <</S /URI /URI '.$this->_textstring($pl[4]).'>> >>';
					}
					else {
						$l=$this->links[$pl[4]];
						// may not be set if #link points to non-existent target
						if (isset($this->pageDim[$l[0]]['h'])) { $htarg=$this->pageDim[$l[0]]['h']*_MPDFK; }
						else { $htarg=$this->h*_MPDFK; } // doesn't really matter
						$annot.=sprintf(' /Dest [%d 0 R /XYZ 0 %.3F null]>>',1+2*$l[0],$htarg-$l[1]*_MPDFK);
					}
					$this->_out($annot);
					$this->_out('endobj');
				   }
				}


	/*-- ANNOTATIONS --*/
				if(isset($this->PageAnnots[$n])) {
				   foreach ($this->PageAnnots[$n] as $key => $pl) {
					if ($pl['opt']['file']) { $FileAttachment=true; }
					else { $FileAttachment=false; }
					$this->_newobj();
					$annot='';
					$pl['opt'] = array_change_key_case($pl['opt'], CASE_LOWER);
					$x = $pl['x']; 
					if ($this->annotMargin <> 0 || $x==0 || $x<0) {	// Odd page
					   $x = ($wPt/_MPDFK) - $this->annotMargin;
					}
					$w = $h = 0;
					$a = $x * _MPDFK;
					$b = $hPt - ($pl['y']  * _MPDFK);
					$annot .= '<</Type /Annot ';
					if ($FileAttachment) { 
						$annot .= '/Subtype /FileAttachment'; 
						// Need to set a size for FileAttachment icons
						if ($pl['opt']['icon']=='Paperclip') { $w=8.235; $h=20; }	// 7,17
						else if ($pl['opt']['icon']=='Tag') { $w=20; $h=16; }
						else if ($pl['opt']['icon']=='Graph') { $w=20; $h=20; }
						else { $w=14; $h=20; } 	// PushPin 
						$f = $pl['opt']['file'];
						$f = preg_replace('/^.*\//', '', $f);
						$f = preg_replace('/[^a-zA-Z0-9._]/', '', $f);
						$annot .= '/FS <</Type /Filespec /F ('.$f.')';
						$annot .= '/EF <</F '.($this->n+1).' 0 R>>';
						$annot .= '>>';
					}
					else { 
						$annot .= '/Subtype /Text'; 
					}
					$rect = sprintf('%.3F %.3F %.3F %.3F', $a, $b-$h, $a+$w, $b);
					$annot .= '/Rect ['.$rect.']';

					// contents = description of file in free text
					$annot .= ' /Contents '.$this->_UTF16BEtextstring($pl['txt']);
					$annot .= ' /NM '.$this->_textstring(sprintf('%04u-%04u', $n, (2000 + $key)));
					$annot .= ' /M '.$this->_textstring('D:'.date('YmdHis'));
					$annot .= ' /CreationDate '.$this->_textstring('D:'.date('YmdHis'));
					$annot .= ' /Border [0 0 0]';
					if ($this->PDFA || $this->PDFX) { 
						$annot .= ' /F 28'; 
						$annot .= ' /CA 1'; 
					}
					else if ($pl['opt']['ca']>0) { $annot .= ' /CA '.$pl['opt']['ca']; }

					$annotcolor = ' /C [';
					if (isset($pl['opt']['c']) AND $pl['opt']['c']) {
						$col = $pl['opt']['c'];
						if ($col{0}==3 || $col{0}==5) { $annotcolor .= sprintf("%.3F %.3F %.3F", ord($col{1})/255,ord($col{2})/255,ord($col{3})/255); }
						else if ($col{0}==1) { $annotcolor .= sprintf("%.3F", ord($col{1})/255); }
						else if ($col{0}==4 || $col{0}==6) { $annotcolor .= sprintf("%.3F %.3F %.3F %.3F", ord($col{1})/100,ord($col{2})/100,ord($col{3})/100,ord($col{4})/100); }
						else { $annotcolor .= '1 1 0'; }
					}
					else { $annotcolor .= '1 1 0'; }
					$annotcolor .= ']';
					$annot .= $annotcolor;
					// Usually Author
					// Use as Title for fileattachment
					if (isset($pl['opt']['t']) AND is_string($pl['opt']['t'])) {
						$annot .= ' /T '.$this->_UTF16BEtextstring($pl['opt']['t']);
					}
					if ($FileAttachment) {
						$iconsapp = array('Paperclip', 'Graph', 'PushPin', 'Tag'); 
					}
					else { $iconsapp = array('Comment', 'Help', 'Insert', 'Key', 'NewParagraph', 'Note', 'Paragraph'); }
					if (isset($pl['opt']['icon']) AND in_array($pl['opt']['icon'], $iconsapp)) {
						$annot .= ' /Name /'.$pl['opt']['icon'];
					}
					else if ($FileAttachment) { $annot .= ' /Name /PushPin'; }
					else { $annot .= ' /Name /Note'; }
					if (!$FileAttachment) {
						// /Subj is PDF 1.5 spec.
						if (isset($pl['opt']['subj']) && !$this->PDFA && !$this->PDFX) {
							$annot .= ' /Subj '.$this->_UTF16BEtextstring($pl['opt']['subj']);
						}
						if (!empty($pl['opt']['popup'])) { 
							$annot .= ' /Open true'; 
							$annot .= ' /Popup '.($this->n+1).' 0 R';
						}
						else { $annot .= ' /Open false'; }
					}
					$annot .= ' /P '.$pl['pageobj'].' 0 R';
					$annot .= '>>';
					$this->_out($annot);
					$this->_out('endobj');

					if ($FileAttachment) { 
						$file = @file_get_contents($pl['opt']['file']) or die('mPDF Error: Cannot access file attachment - '.$pl['opt']['file']);
						$filestream = gzcompress($file);
						$this->_newobj();
						$this->_out('<</Type /EmbeddedFile');
						$this->_out('/Length '.strlen($filestream));
						$this->_out('/Filter /FlateDecode');
						$this->_out('>>');
						$this->_putstream($filestream);
						$this->_out('endobj');
					}
					else if (!empty($pl['opt']['popup'])) { 
						$this->_newobj();
						$annot='';
						if (is_array($pl['opt']['popup']) && isset($pl['opt']['popup'][0])) { $x = $pl['opt']['popup'][0] * _MPDFK; }
						else { $x = $pl['x'] * _MPDFK; }
						if (is_array($pl['opt']['popup']) && isset($pl['opt']['popup'][1])) { $y = $hPt - ($pl['opt']['popup'][1] * _MPDFK); }
						else { $y = $hPt - ($pl['y']  * _MPDFK); }
						if (is_array($pl['opt']['popup']) && isset($pl['opt']['popup'][2])) { $w = $pl['opt']['popup'][2] * _MPDFK; }
						else { $w = 180; }
						if (is_array($pl['opt']['popup']) && isset($pl['opt']['popup'][3])) { $h = $pl['opt']['popup'][3] * _MPDFK; }
						else { $h = 120; }
						$rect = sprintf('%.3F %.3F %.3F %.3F', $x, $y-$h, $x+$w, $y);
						$annot .= '<</Type /Annot /Subtype /Popup /Rect ['.$rect.']';
						$annot .= ' /M '.$this->_textstring('D:'.date('YmdHis'));
						if ($this->PDFA || $this->PDFX) { $annot .= ' /F 28'; }
						$annot .= ' /Parent '.($this->n-1).' 0 R';
						$annot .= '>>';
						$this->_out($annot);
						$this->_out('endobj');
					}
				   }
				}
	/*-- END ANNOTATIONS --*/

	/*-- FORMS --*/
				// Active Forms
				if ( count($this->form->forms) > 0 ) {
					$this->form->_putFormItems($n, $hPt);
				}
	/*-- END FORMS --*/
			}
		}
	/*-- FORMS --*/
		// Active Forms - Radio Button Group entries
		// Output Radio Button Group form entries (radio_on_obj_id already determined)
		if (count($this->form->form_radio_groups)) {
			$this->form->_putRadioItems($n);
		}
	/*-- END FORMS --*/
	}

}
?>