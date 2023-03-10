<?php

namespace Modules\XmlFormGenerator\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PhpXml\XmlInput;
use PhpXml\XmlOutput;
use PhpXml\XmlSchema;
use PhpXml\XmlNode;
use PhpXml\XmlValidator;

class XmlFormGeneratorController extends Controller
{
    public function index()
    {
        return view('xml-form-generator::index');
    }

    public function generate(Request $request)
    {
        $xsd_file = $request->file('xsd_file');
        $xsd_data = file_get_contents($xsd_file->getRealPath());

        // Parse de XSD
        $xsd = new XmlSchema();
        $xsd->parseString($xsd_data);

        // Maak het formulier
        $form = '';
        foreach ($xsd->elements() as $element) {
            $form .= '<div class="form-group">';
            $form .= '<label for="'.$element->name().'">'.$element->name().'</label>';
            $form .= $this->generateInputField($element);
            $form .= '</div>';
        }

        // Maak de XML
        $xml = new XmlNode($xsd->root()->name());
        $this->generateXmlNode($xml, $xsd->root(), $request->input());

        // Valideer de XML tegen het XSD
        $validator = new XmlValidator($xsd);
        $errors = $validator->validate($xml);
        if (!empty($errors)) {
            return redirect('/xml-form-generator')->withErrors($errors);
        }

        // Exporteer de XML
        $xml_output = new XmlOutput();
        $xml_output->setIndent(true);
        $xml_output->setIndentString("    ");
        $xml_output->openMemory();
        $xml_output->startDocument();
        $xml_output->writeNode($xml);
        $xml_output->endDocument();

        return response($xml_output->outputMemory())->header('Content-Type', 'application/xml');
    }

    private function generateInputField($element)
    {
        $input_type = 'text';
        if ($element->type() == 'xs:boolean') {
            $input_type = 'checkbox';
        } elseif ($element->type() == 'xs:integer' || $element->type() == 'xs:decimal') {
            $input_type = 'number';
        }

        return '<input type="'.$input_type.'" name="'.$element->name().'" class="form-control">';
    }

    private function generateXmlNode(&$xml_node, $xsd_node, $input_data)
    {
        foreach ($xsd_node->children() as $child_node) {
            $child_name = $child_node->name();
            $child_type = $child_node->type();

            if (isset($input_data[$child_name])) {
                $value = $input_data[$child_name];
            } else {
                $value = $child_node->default();
            }

            if (!empty($value)) {
                if ($child_node->isScalar()) {
                    $xml_node->addAttribute($child_name, $value);
                } else {
                    $child_xml_node = new XmlNode($child_name);
                    $this->generateXmlNode($child_xml_node, $child_node, $input_data);
                    $xml_node->addChild($child_xml_node);
                }
            }
        }
    }
}