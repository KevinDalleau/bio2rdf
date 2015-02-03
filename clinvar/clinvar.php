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
      
      while($xml->parse("ClinVarAssertion") == TRUE) {
        
        if(isset($this->id_list) and count($this->id_list) == 0) break;
        $y = $xml->GetXMLRoot();
        var_dump($y);
        // $this->parse_clinvar_assertion($xml);

      }
      unset($xml);
    }

    function parse_clinvar_assertion(&$xml) {
      $x = $xml->GetXMLRoot();
      
    }

  }



?>
