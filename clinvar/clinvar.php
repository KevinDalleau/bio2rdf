<?php


/**
* ClinVar RDFizer
* @version 1.0
* @author Kevin Dalleau
* @description
*/
require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');
require_once(__DIR__.'/../../php-lib/xmlapi.php');

class ClinVarParser extends Bio2RDFizer
{
  function __construct($argv) {
    parent::__construct($argv,"clinvar");
    parent::addParameter('files', true, 'all|clinvar','all','Files to convert');
    parent::addParameter('download_url',false,null,'ftp://ftp.ncbi.nlm.nih.gov/pub/clinvar/xml/');
    parent::initialize();
  }

  function Run()
  {
    $file         = "clinvar_exemple.xml";
    $indir        = parent::getParameterValue('indir');
    $outdir       = parent::getParameterValue('outdir');
    $download_url = parent::getParameterValue('download_url');

    $lfile = $indir.$file;
    if(!file_exists($lfile)) {
      trigger_error($file." not found. Will attempt to download.", E_USER_NOTICE);
      parent::setParameterValue('download',true);
    }
    //download
    $rfile = $outdir.$file;
    if($this->GetParameterValue('download') == true){
      echo "downloading $file ... ";
      utils::downloadSingle($rfile,$lfile);
    }

    $ofile = 'clinvar.'.parent::getParameterValue('output_format');
    $gz= strstr(parent::getParameterValue('output_format'), "gz")?$gz=true:$gz=false;

    parent::setReadFile($lfile);
    parent::setWriteFile($outdir.$ofile, $gz);
    echo "processing $file... ";
    //$this->process();
    echo "done!".PHP_EOL;
    parent::getWriteFile()->close();


      // dataset description
      $ouri = parent::getGraphURI();
      parent::setGraphURI(parent::getDatasetURI());

      $source_version = parent::getDatasetVersion();
      $bVersion = parent::getParameterValue('bio2rdf_release');
      $prefix = parent::getPrefix();
      $date = date ("Y-m-d\TH:i:sP");
      // dataset description
      $source_file = (new DataResource($this))
      ->setURI($rfile)
      ->setTitle("ClinVar")
      ->setRetrievedDate( date ("Y-m-d\TH:i:sP", filemtime($indir."Clinvar")))
      ->setFormat("application/xml")
      ->setFormat("application/zip")
      ->setPublisher("http://www.ncbi.nlm.nih.gov/clinvar/")
      ->setHomepage("http://www.ncbi.nlm.nih.gov/clinvar/")
      ->setRights("use")
      ->setRights("by-attribution")
      ->setRights("no-commercial")
      ->setLicense("http://www.ncbi.nlm.nih.gov/clinvar/intro/")
      ->setDataset("http://identifiers.org/clinvar/");

      $output_file = (new DataResource($this))
      ->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$cfile")
      ->setTitle("Bio2RDF v$bVersion RDF version of $prefix v$source_version")
      ->setSource($source_file->getURI())
      ->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/clinvar/clinvar.php")
      ->setCreateDate($date)
      ->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
      ->setPublisher("http://bio2rdf.org")
      ->setRights("use-share-modify")
      ->setRights("by-attribution")
      ->setRights("restricted-by-source-license")
      ->setLicense("http://creativecommons.org/licenses/by/3.0/")
      ->setDataset(parent::getDatasetURI());

      $gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
      if($gz) $output_file->setFormat("application/gzip");
      if(strstr(parent::getParameterValue('output_format'),"nt")) $output_file->setFormat("application/n-triples");
      else $output_file->setFormat("application/n-quads");

      $dataset_description = $source_file->toRDF().$output_file->toRDF();
      echo "Generating dataset description... ";
      $this->parse_clinvar($indir,$file);
      parent::setWriteFile($outdir.parent::getBio2RDFReleaseFile());
      parent::getWriteFile()->write($dataset_description);
      parent::getWriteFile()->close();
    }
    // function process() {
      
      
    //   parent::AddRDF(
    //   //parent::describeIndividual($this->getNamespace().'2', 'clinlabel', parent::getVoc().'clinvarparent')
    //   //parent::triplify($this->getNamespace().'2', $this->getVoc().'sequence-individual', 'clinvar:sequence_length')
      
    //                 parent::describeIndividual($id, $title, parent::getVoc()."ClinVar").
    //                 parent::triplify($id, parent::getVoc()."title", $title)         


                 
    //     );

    // $this->WriteRDFBufferToWriteFile();
    // contiue;

    // }

    function parse_clinvar($ldir,$infile)
    {
      $xml = new CXML($ldir,$infile);
      
      while($xml->parse("ClinVarSet") == TRUE) {
        
        if(isset($this->id_list) and count($this->id_list) == 0) break;
        $file_content="";
        $xml_root = $xml->GetXMLRoot();
        $id = $xml->GetAttributeValue($xml_root,'ID'); //ID of ClinVarSet
        $file_content.="ID :".$id."\n";
        $title = $xml_root->{"Title"};
        $file_content.="Title :".$title."\n";

        //$file = fopen($id.".txt","w");
        
        $record_status = $xml_root->{"RecordStatus"};

        $rcva_node = $xml_root->ReferenceClinVarAssertion; //ReferenceClinVarAssertion node
          $rcva_date_created = $xml->GetAttributeValue($rcva_node,'DateCreated');
          $rcva_record_status = $rcva_node->{"RecordStatus"};

          $cva_node = $rcva_node->ClinVarAccession; //ClinVarAccession node
            $cva_acc = $xml->GetAttributeValue($cva_node,'Acc');
            $cva_version = $xml->GetAttributeValue($cva_node,'Version');
            $cva_type = $xml->GetAttributeValue($cva_node,'Type');
            $cva_date_updated = $xml->GetAttributeValue($cva_node,'DateUpdated');

          $clin_sig_node = $rcva_node->ClinicalSignificance; //Clinical Significance node
            $clin_sig_last_eval = $xml->GetAttributeValue($clin_sig_node,'DateLastEvaluated');
            $clin_sig_review_status = $clin_sig_node->{'ReviewStatus'};
            $clin_sig_desc = $clin_sig_node->{'Description'};

          $assertion_node = $rcva_node->Assertion;
            $assertion = $xml->GetAttributeValue($assertion_node,'Type'); //Example : Variation to disease
            $file_content.="Type of assertion : ".$assertion."\n";

          $attribute_set_node = $rcva_node->AttributeSet; //Atribute Set node
            $attribute_node = $attribute_set_node->Attribute; //Example : "Autosomal unknown"
            if($attribute_node!=NULL) {
              $attribute = $attribute_node->{'Attribute'};
              $attribute_type = $xml->GetAttributeValue($attribute_node, 'Type'); //Example : ModeOfInheritance
              $file_content.=$attribute_type." : ".$assertion."\n";
              $attribute_int_value = $xml->GetAttributeValue($attribute_node,'integerValue');
          
            }
          // $observed_in_node = $rcva_node->ObservedIn;
          //   $sample_node = $observed_in_node->Sample;
          //     $sample_origin = $sample_node->Origin;
          //     $species = $sample_node->Species;
          //     $species_taxonomyId = $xml->GetAttributeValue($species,'TaxonomyId');
          //     $affected_status = $sample_node->AffectedStatus;
          //   $method_node = $observed_in_node->Method;
          //     $method_purpose = $method_node->{'Purpose'};
          //     $method_type = $method_node->{'MethodType'};
          //   $observed_data_node = $observed_in_node->ObservedData;
          //     $observed_data_id = $xml->GetAttributeValue($observed_data_node,'ID');
          //     $observed_data_attr = $observed_data_node->Attribute;
          //     $observed_data_attr_integerValue = $xml->GetAttributeValue($observed_data_attr,'integerValue');
          //     $observed_data_attr_type = $xml->GetAttributeValue($observed_data_attr,'Type');
              
          $measureset_node = $rcva_node->MeasureSet;
          $measureset_type = $xml->GetAttributeValue($measureset_node,'Type'); //Example : Variant
          $measureset_id = $xml->GetAttributeValue($measureset_node,'ID'); 
            $measure = $measureset_node->Measure;
            $measure_type = $xml->GetAttributeValue($measure,'Type'); //Example : single nucleotide variant
            $measure_id = $xml->GetAttributeValue($measure,'ID');
              $measure_name = $measure->Name;
                $measure_name_elementvalue = $measure_name->ElementValue; //Example : p.Ser524_Leu525insPro
                $measure_name_type = $xml->GetAttributeValue($measure_name_elementvalue,'Type');
              $measure_attributeset = $measure->AttributeSet;
                $measure_attribute = $measure_attributeset->Attribute; //Example : p.Ser524_Leu525insPro
                if($measure_attribute != NULL) {
                  $measure_attribute_type = $xml->GetAttributeValue($measure_attribute,'Type'); //Example : HGVS, protein
                }
              $measure_cytogeneticloc = $measure->CytogeneticLocation;
              $measure_relationship = $measure->MeasureRelationship;
                $measure_relationship_type = $xml->GetAttributeValue($measure_relationship,'Type'); //Example: Variant in gene
                $measure_relationship_name = $measure_relationship->Name;
                  $measure_relationship_name_elementvalue = $measure_relationship_name->ElementValue; //Example: ALMS2
                  if($measure_relationship_name_elementvalue!=NULL) {
                    $measure_relationship_name_elementvalue_type = $xml->GetAttributeValue($measure_relationship_name_elementvalue,'Type'); //Example: Prefered
                  }
                $measure_relationship_symbol = $measure_relationship->Symbol;
                  $symbol_elementvalue = $measure_relationship_symbol->ElementValue;
                  if($measure_relationship->SequenceLocation != NULL) {
                    $measure_relationship_sequencelocation = $measure_relationship->SequenceLocation;
                if($measure_relationship_sequencelocation!=NULL) {
                  $sequence_location_assembly = $xml->GetAttributeValue($measure_relationship_sequencelocation,'Assembly');
                  $sequence_location_display_stop = $xml->GetAttributeValue($measure_relationship_sequencelocation,'display_stop');
                  $sequence_location_display_start = $xml->GetAttributeValue($measure_relationship_sequencelocation,'display_start');
                  $sequence_location_chr = $xml->GetAttributeValue($measure_relationship_sequencelocation,'Chr');
                  $sequence_location_accession = $xml->GetAttributeValue($measure_relationship_sequencelocation,'Accession'); //Example : NC_000002.12
                  $sequence_location_start = $xml->GetAttributeValue($measure_relationship_sequencelocation,'start');
                  $sequence_location_stop = $xml->GetAttributeValue($measure_relationship_sequencelocation,'stop');
                  $sequence_location_strand = $xml->GetAttributeValue($measure_relationship_sequencelocation,'Strand');      
                  
                $file_content.="Chromosome : ".$sequence_location_chr."\n";
                $file_content.="Sequence assembly : ".$sequence_location_assembly."\n";
                $file_content.="Chromosome accession : ".$sequence_location_accession."\n";
                }
                
                
                  foreach($measure_relationship->XRef as $xrefel) {
                  $xref_id = $xml->GetAttributeValue($xrefel,"ID");
                  $xref_db = $xml->GetAttributeValue($xrefel,"DB");
                  $file_content.=$xref_db.": ".$xref_id."\n";
                    // var_dump($xref_id.' '.$xref_db);
                    // var_dump($xrefel);
                  }
          $traitset_node = $rcva_node->TraitSet; //TraitSet node        
            $traitset_type=$xml->GetAttributeValue($traitset_node,"Type");
            $traitset_id=$xml->GetAttributeValue($traitset_node,"ID");
              $traitset_trait_node = $traitset_node->Trait; //Attention : cas où il y en a plusieurs
              $trait_type=$xml->GetAttributeValue($traitset_trait_node,"Type");
              $trait_id=$xml->GetAttributeValue($traitset_trait_node,"ID");
              $file_content.="Trait : ".$trait_id.", de type ".$trait_type."\n";

                $trait_name_node=$traitset_trait_node->Name;
                 $trait_name=$traitset_name_node->ElementValue;
                 $trait_ref_array = array();
                 
              $trait_name_symbol->$traitset_trait_node->Symbol->ElementValue;
                $file_content.="Symbole du trait : ".$trait_name_symbol;
                    parent::AddRDF(
      //parent::describeIndividual($this->getNamespace().'2', 'clinlabel', parent::getVoc().'clinvarparent')
      //parent::triplify($this->getNamespace().'2', $this->getVoc().'sequence-individual', 'clinvar:sequence_length')
      
                    parent::describeIndividual("clinvar:".$cva_acc, $title, parent::getVoc()."clinvar").
                    parent::triplifyString("clinvar:".$cva_acc, parent::getVoc()."title", $$measure_name).
                    parent::triplifyString("clinvar:".$cva_acc, parent::getVoc()."assertion", $assertion).
                    parent::triplifyString("clinvar:".$cva_acc, parent::getVoc()."gene_symbol", $symbol_elementvalue).
                    parent::triplifyString("clinvar:".$cva_acc, parent::getVoc()."gene_accession", $sequence_location_accession).
                    parent::triplifyString("clinvar:".$cva_acc, parent::getVoc()."chromosome", $sequence_location_chr).
                    parent::triplifyString("clinvar:".$cva_acc, parent::getVoc()."sequence_assembly", $sequence_location_assembly).
                    parent::triplifyString("clinvar:".$cva_acc, parent::getVoc()."trait", $trait_name)
                    
                   // parent::triplifyString("clinvar:".$id, parent::getVoc()."trait", $trait_name_symbol)
                   // parent::triplifyString("clinvar:".$id, parent::getVoc()."trait", $trait_name_symbol)



                 
        );
        foreach($trait_name_node->XRef as $xrefname) {
                  $xref_id = $xml->GetAttributeValue($xrefname,"ID");
                  $xref_db = $xml->GetAttributeValue($xrefname,"DB");
                  parent::AddRDF(
                    parent::triplifyString("clinvar:".$cva_acc, parent::getVoc()."trait_source", $xref_db.": ".$xref_id)
                  );
                  
                  };

    $this->WriteRDFBufferToWriteFile();
    continue;


                 // fwrite($file,$file_content."\n");
                 // fclose($file);
        
                  }
                

      }
      unset($xml);
      
    }

    function parse_clinvar_assertion(&$xml) {
      $x = $xml->GetXMLRoot();
      
    }

  }



?>
