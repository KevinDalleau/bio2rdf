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
    $file         = "ClinVar.xml";
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
    $this->process();
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
    function process() {
      
      
      parent::AddRDF(
      parent::describeIndividual($this->getNamespace().'2', 'clinlabel', parent::getVoc().'clinvarparent')
      //parent::triplify($this->getNamespace().'2', $this->getVoc().'sequence-individual', 'clinvar:sequence_length')
        );

    $this->WriteRDFBufferToWriteFile();

    }

    function parse_clinvar($ldir,$infile)
    {
      $xml = new CXML($ldir,$infile);
      
      while($xml->parse("ClinVarSet") == TRUE) {
        
        if(isset($this->id_list) and count($this->id_list) == 0) break;
        $xml_root = $xml->GetXMLRoot();
        $id = $xml->GetAttributeValue($xml_root,'ID'); //ID of ClinVarSet
        $title = $xml_root->{"Title"};
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
            $assertion = $xml->GetAttributeValue($assertion_node,'Type');

          $attribute_set_node = $rcva_node->AttributeSet; //Atribute Set node
            $attribute_node = $attribute_set_node->Attribute;
            if($attribute_node!=NULL) {
              $attribute = $attribute_node->{'Attribute'};
              $attribute_int_value = $xml->GetAttributeValue($attribute_node,'integerValue');
              // var_dump($attribute_node);
        //echo $attribute."\n";
            }
          $observed_in_node = $rcva_node->ObservedIn;
            $sample_node = $observed_in_node->Sample;
              $sample_origin = $sample_node->Origin;
              $species = $sample_node->Species;
              $species_taxonomyId = $xml->GetAttributeValue($species,'TaxonomyId');
              $affected_status = $sample_node->AffectedStatus;
            $method_node = $observed_in_node->Method;
              $method_purpose = $method_node->{'Purpose'};
              $method_type = $method_node->{'MethodType'};
            $observed_data_node = $observed_in_node->ObservedData;
              $observed_data_id = $xml->GetAttributeValue($observed_data_node,'ID');
              $observed_data_attr = $observed_data_node->Attribute;
              $observed_data_attr_integerValue = $xml->GetAttributeValue($observed_data_attr,'integerValue');
              $observed_data_attr_type = $xml->GetAttributeValue($observed_data_attr,'Type');
              
          $measureset_node = $rcva_node->MeasureSet;
          $measureset_type = $xml->GetAttributeValue($measureset_node,'Type');
          $measureset_id = $xml->GetAttributeValue($measureset_node,'ID');
            $measure = $measureset_node->Measure;
            $measure_type = $xml->GetAttributeValue($measure,'Type');
            $measure_id = $xml->GetAttributeValue($measure,'ID');
              $measure_name = $measure->Name;
                $measure_name_elementvalue = $measure_name->ElementValue;
                $measure_name_type = $xml->GetAttributeValue($measure_name_elementvalue,'Type');
              $measure_attributeset = $measure->AttributeSet;
                $measure_attribute = $measure_attributeset->Attribute;
                if($measure_attribute != NULL) {
                  $measure_attribute_type = $xml->GetAttributeValue($measure_attribute,'Type');
                }
              $measure_cytogeneticloc = $measure->CytogeneticLocation;

              
              echo $measure_cytogeneticloc;




        

      }
      unset($xml);
    }

    function parse_clinvar_assertion(&$xml) {
      $x = $xml->GetXMLRoot();
      
    }

  }



?>
