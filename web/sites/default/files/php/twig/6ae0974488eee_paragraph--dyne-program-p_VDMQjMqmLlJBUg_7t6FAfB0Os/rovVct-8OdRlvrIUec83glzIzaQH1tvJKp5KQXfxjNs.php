<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTestError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* themes/custom/dynetx/templates/paragraph--dyne-program-pipeline.html.twig */
class __TwigTemplate_47e860158223ca9034e669fe3d9494b7 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 49
        yield "
";
        // line 50
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_pipeline_cache"] ?? null), "html", null, true);
        yield "
";
        // line 51
        if (((($tmp = ($context["dyne_pipeline_intro_heading"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) || (($tmp = ($context["dyne_pipeline_intro"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
            // line 52
            yield "  <div class=\"container-fluid section pipeline-intro page-intro\"><div class=\"container\"><div class=\"row\"><div class=\"col-12 intro\">
    ";
            // line 53
            if ((($tmp = ($context["dyne_pipeline_intro_heading"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<h1 class=\"h2 h2-new-sm h2-new-thin mb-4 pb-xl-2\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_pipeline_intro_heading"] ?? null), "html", null, true);
                yield "</h1>";
            }
            // line 54
            yield "    ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_pipeline_intro"] ?? null), "html", null, true);
            yield "
  </div></div></div></div>
";
        }
        // line 57
        yield "<span id=\"programs\" class=\"anchor\" aria-hidden=\"true\"></span>
<div class=\"container-fluid section pipeline-chart-26 bg-wht-blue\">
  <div class=\"anchor\" id=\"chart\"></div>
  <div class=\"container\">
    ";
        // line 61
        if ((($tmp = ($context["dyne_pipeline_heading"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<h3>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_pipeline_heading"] ?? null), "html", null, true);
            yield "</h3>";
        }
        // line 62
        yield "    <div class=\"row chart-headline\">
      <div class=\"col-12 col-lg-3\"><span>";
        // line 63
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("DISEASE & PREVALENCE"));
        yield "<span class=\"d-lg-none\">/";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("TARGET"));
        yield "</span></span></div>
      <div class=\"col d-none d-lg-flex\">";
        // line 64
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("TARGET"));
        yield "</div>
      <div class=\"col-8 stages\"><div class=\"row\">
        <div class=\"col-3 stage-1\"><span>";
        // line 66
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("PRECLINICAL"));
        yield "</span></div>
        <div class=\"col-3 stage-2\"><span>";
        // line 67
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("CLINICAL"));
        yield "</span></div>
        <div class=\"col-3 stage-3\"><span>";
        // line 68
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("REGISTRATIONAL"));
        yield "</span></div>
        <div class=\"col-3 stage-4\"><span>";
        // line 69
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Commercial"));
        yield "</span></div>
      </div></div>
    </div>
    ";
        // line 72
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["dyne_pipeline_groups"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["group"]) {
            // line 73
            yield "      ";
            $context["primary"] = CoreExtension::getAttribute($this->env, $this->source, $context["group"], "program", [], "any", false, false, true, 73);
            // line 74
            yield "      <div class=\"row chart-body smooth-scroll ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "style", [], "any", false, false, true, 74), "html", null, true);
            yield "\">
        <div class=\"col-12 col-lg-3\">
          ";
            // line 76
            if (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "anchor", [], "any", false, false, true, 76)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "has_detail", [], "any", false, false, true, 76)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
                yield "<a class=\"btn btn-primary btn-plus btn-plus-collapse collapsed\" data-mdb-collapse-init data-mdb-target=\"#collapse";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "anchor", [], "any", false, false, true, 76), "html", null, true);
                yield "\" aria-expanded=\"false\" aria-controls=\"collapse";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "anchor", [], "any", false, false, true, 76), "html", null, true);
                yield "\" href=\"#";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "anchor", [], "any", false, false, true, 76), "html", null, true);
                yield "\"><i aria-hidden=\"true\"></i><span class=\"sr-only\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "disease", [], "any", false, false, true, 76), "html", null, true);
                yield " ";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("information"));
                yield "</span></a>";
            }
            // line 77
            yield "          ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::replace(CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "disease", [], "any", false, false, true, 77), ["Duchenne muscular" => "Duchenne muscular", "Myotonic dystrophy" => "Myotonic dystrophy"]), "html", null, true);
            yield "
          ";
            // line 78
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "prevalence_us", [], "any", false, false, true, 78)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<div class=\"mt-2\"><span>";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("U.S.:"));
                yield "</span> ";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "prevalence_us", [], "any", false, false, true, 78), "html", null, true);
                yield "</div>";
            }
            // line 79
            yield "          ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "prevalence_europe", [], "any", false, false, true, 79)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<div><span>";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Europe:"));
                yield "</span> ";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "prevalence_europe", [], "any", false, false, true, 79), "html", null, true);
                yield "</div>";
            }
            // line 80
            yield "        </div>
        <div class=\"col target\">";
            // line 81
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["group"], "items", [], "any", false, false, true, 81));
            foreach ($context['_seq'] as $context["_key"] => $context["program"]) {
                yield "<div>";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["program"], "target", [], "any", false, false, true, 81), "html", null, true);
                yield "</div>";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['program'], $context['_parent']);
            $context = array_intersect_key($context, $_parent);
            $context += $_parent;
            yield "</div>
        <div class=\"col-12 col-lg-8 chart-wrapper\">
          ";
            // line 83
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["group"], "items", [], "any", false, false, true, 83));
            foreach ($context['_seq'] as $context["_key"] => $context["program"]) {
                // line 84
                yield "            <div class=\"inner\">
              ";
                // line 85
                if ((Twig\Extension\CoreExtension::length($this->env->getCharset(), CoreExtension::getAttribute($this->env, $this->source, $context["group"], "items", [], "any", false, false, true, 85)) > 1)) {
                    yield "<div class=\"d-lg-none\">";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["program"], "target", [], "any", false, false, true, 85), "html", null, true);
                    yield "</div>";
                }
                // line 86
                yield "              ";
                if ( !(null === CoreExtension::getAttribute($this->env, $this->source, $context["program"], "progress", [], "any", false, false, true, 86))) {
                    yield "<div class=\"chart short\" style=\"width: ";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["program"], "progress", [], "any", false, false, true, 86), "html", null, true);
                    yield "%\"><div>";
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["program"], "short_name", [], "any", false, false, true, 86)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        yield "<div>";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["program"], "display_name", [], "any", false, false, true, 86), "html", null, true);
                        yield "</div>";
                    } else {
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["program"], "display_name", [], "any", false, false, true, 86), "html", null, true);
                    }
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["program"], "phase", [], "any", false, false, true, 86)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        yield "<span>";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["program"], "phase", [], "any", false, false, true, 86), "html", null, true);
                        yield "</span>";
                    }
                    yield "</div></div>";
                } else {
                    yield "<div>";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["program"], "display_name", [], "any", false, false, true, 86), "html", null, true);
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["program"], "phase", [], "any", false, false, true, 86)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        yield " <span>";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["program"], "phase", [], "any", false, false, true, 86), "html", null, true);
                        yield "</span>";
                    }
                    yield "</div>";
                }
                // line 87
                yield "              ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["program"], "status", [], "any", false, false, true, 87)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    yield "<div class=\"eta\"><i class=\"fa-sharp fa-solid fa-star\" aria-hidden=\"true\"></i><div>";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["program"], "status", [], "any", false, false, true, 87), "html", null, true);
                    yield "</div></div>";
                }
                // line 88
                yield "            </div>
          ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['program'], $context['_parent']);
            $context = array_intersect_key($context, $_parent);
            $context += $_parent;
            // line 90
            yield "        </div>
      </div>
      ";
            // line 92
            if (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "anchor", [], "any", false, false, true, 92)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "has_detail", [], "any", false, false, true, 92)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
                yield "<div class=\"row pipeline-description collapse\" id=\"collapse";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["primary"] ?? null), "anchor", [], "any", false, false, true, 92), "html", null, true);
                yield "\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->getTemplateForMacro("macro_details", $context, 92, $this->getSourceContext())->macro_details(...[($context["primary"] ?? null), true]));
                yield "</div>";
            }
            // line 93
            yield "    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['group'], $context['_parent']);
        $context = array_intersect_key($context, $_parent);
        $context += $_parent;
        // line 94
        yield "    ";
        if (((($tmp = ($context["dyne_pipeline_expansion_heading"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) || (($tmp = ($context["dyne_pipeline_expansion_targets"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
            // line 95
            yield "      <div class=\"row chart-headline\"><div class=\"col-12\"><span>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_pipeline_expansion_heading"] ?? null), "html", null, true);
            yield "</span> <div class=\"ps-lg-2\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_pipeline_expansion_targets"] ?? null), "html", null, true);
            yield "</div></div></div>
    ";
        }
        // line 97
        yield "    ";
        if (((($tmp = ($context["dyne_pipeline_expansion_title"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) || (($tmp = ($context["dyne_pipeline_expansion_body"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
            // line 98
            yield "      <div class=\"row pipeline-description collapse\" id=\"collapseEXP\"><div class=\"col-12 py-4\">
        ";
            // line 99
            if ((($tmp = ($context["dyne_pipeline_expansion_title"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<h2 class=\"h1\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_pipeline_expansion_title"] ?? null), "html", null, true);
                yield "</h2>";
            }
            // line 100
            yield "        ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_pipeline_expansion_body"] ?? null), "html", null, true);
            yield "
        <a class=\"btn btn-primary mt-4 mt-lg-0\" href=\"#chart\">";
            // line 101
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Back to pipeline"));
            yield "</a>
      </div></div>
    ";
        }
        // line 104
        yield "  </div>
</div>
";
        // line 106
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["dyne_pipeline_details"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["program"]) {
            // line 107
            yield "  <div class=\"container-fluid section pipeline-description\">
    <div id=\"";
            // line 108
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["program"], "anchor", [], "any", false, false, true, 108), "html", null, true);
            yield "\" class=\"anchor\"></div>
    ";
            // line 109
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->getTemplateForMacro("macro_details", $context, 109, $this->getSourceContext())->macro_details(...[$context["program"]]));
            yield "
  </div>
";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['program'], $context['_parent']);
        $context = array_intersect_key($context, $_parent);
        $context += $_parent;
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["dyne_pipeline_cache", "dyne_pipeline_intro_heading", "dyne_pipeline_intro", "dyne_pipeline_heading", "dyne_pipeline_groups", "dyne_pipeline_expansion_heading", "dyne_pipeline_expansion_targets", "dyne_pipeline_expansion_title", "dyne_pipeline_expansion_body", "dyne_pipeline_details", "mobile", "show_back", "back_url"]);        yield from [];
    }

    // line 1
    public function macro_details($program = null, $mobile = false, $back_url = "#chart", $show_back = true, ...$varargs): string|Markup
    {
        $macros = $this->macros;
        $context = [
            "program" => $program,
            "mobile" => $mobile,
            "back_url" => $back_url,
            "show_back" => $show_back,
            "varargs" => $varargs,
        ] + $this->env->getGlobals();

        $blocks = [];

        return ('' === $tmp = implode('', iterator_to_array((function () use (&$context, $macros, $blocks) {
            // line 2
            yield "  ";
            if ( !(($tmp = ($context["mobile"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<div class=\"container\">";
            }
            // line 3
            yield "  ";
            if (((((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "heading", [], "any", false, false, true, 3)) && $tmp instanceof Markup ? (string) $tmp : $tmp) || (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "body", [], "any", false, false, true, 3)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) || (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "trials", [], "any", false, false, true, 3)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) || (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 3)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
                // line 4
                yield "    <div class=\"row";
                if ( !(($tmp = ($context["mobile"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    if ((CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "style", [], "any", false, false, true, 4) == "program-1")) {
                        yield " rounded-blue-tail-right full-width";
                    } else {
                        yield " rounded-blue-tail-left";
                    }
                }
                yield "\">
      ";
                // line 5
                if (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 5)) && $tmp instanceof Markup ? (string) $tmp : $tmp) &&  !(($tmp = ($context["mobile"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
                    // line 6
                    yield "        ";
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "heading", [], "any", false, false, true, 6)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        yield "<div class=\"col-12";
                        if ((CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "style", [], "any", false, false, true, 6) == "program-1")) {
                            yield " restricted-width";
                        }
                        yield "\"><h2 class=\"h1\">";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "heading", [], "any", false, false, true, 6), "html", null, true);
                        yield "</h2></div>";
                    }
                    // line 7
                    yield "        <div class=\"col-12 col-lg-6\">
      ";
                } else {
                    // line 9
                    yield "        <div class=\"col-12";
                    if ((($tmp = ($context["mobile"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        yield " py-4";
                    }
                    yield "\">
        ";
                    // line 10
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "heading", [], "any", false, false, true, 10)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        yield "<h2";
                        if (((($tmp = ($context["mobile"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) || (CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "style", [], "any", false, false, true, 10) == "program-1"))) {
                            yield " class=\"h1\"";
                        }
                        yield ">";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "heading", [], "any", false, false, true, 10), "html", null, true);
                        yield "</h2>";
                    }
                    // line 11
                    yield "      ";
                }
                // line 12
                yield "        ";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "body", [], "any", false, false, true, 12), "html", null, true);
                yield "
        ";
                // line 13
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "trials", [], "any", false, false, true, 13));
                foreach ($context['_seq'] as $context["_key"] => $context["link"]) {
                    yield "<p class=\"mt-5 mt-lg-0\"><a class=\"btn ";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "trial_class", [], "any", false, false, true, 13), "html", null, true);
                    yield " btn-lg\" href=\"";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["link"], "url", [], "any", false, false, true, 13), "html", null, true);
                    yield "\">";
                    yield (string) (((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["link"], "title", [], "any", false, false, true, 13)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ($this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["link"], "title", [], "any", false, false, true, 13), "html", null, true)) : ($this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["link"], "url", [], "any", false, false, true, 13), "html", null, true)));
                    yield " <i class=\"ico-play-wht ico-xs\" aria-hidden=\"true\"></i></a></p>";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['link'], $context['_parent']);
                $context = array_intersect_key($context, $_parent);
                $context += $_parent;
                // line 14
                yield "      </div>
      ";
                // line 15
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 15)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 16
                    yield "        <div class=\"col-12 col-lg-6 d-flex flex-column justify-content-center\">
          ";
                    // line 17
                    if (((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 17), "cover", [], "any", false, false, true, 17)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 17), "embed_id", [], "any", false, false, true, 17)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
                        // line 18
                        yield "            <div class=\"embed-responsive-16x9 custom-cover custom-cover-fancy\" data-dyne-video=\"";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 18), "url", [], "any", false, false, true, 18), "html", null, true);
                        yield "\" embd-data-url=\"";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 18), "embed_id", [], "any", false, false, true, 18), "html", null, true);
                        yield "\" embd-data-title=\"";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 18), "title", [], "any", false, false, true, 18), "html", null, true);
                        yield "\" aria-label=\"";
                        yield (string) (((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 18), "title", [], "any", false, false, true, 18)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ($this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 18), "title", [], "any", false, false, true, 18), "html", null, true)) : (t("Watch video")));
                        yield "\">
              <img class=\"img-fluid\" src=\"";
                        // line 19
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 19), "cover", [], "any", false, false, true, 19), "url", [], "any", false, false, true, 19), "html", null, true);
                        yield "\" alt=\"";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 19), "cover", [], "any", false, false, true, 19), "alt", [], "any", false, false, true, 19), "html", null, true);
                        yield "\">
            </div>
          ";
                    } elseif ((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source,                     // line 21
($context["program"] ?? null), "video", [], "any", false, false, true, 21), "cover", [], "any", false, false, true, 21)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        yield "<a href=\"";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 21), "url", [], "any", false, false, true, 21), "html", null, true);
                        yield "\"><img class=\"img-fluid\" src=\"";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 21), "cover", [], "any", false, false, true, 21), "url", [], "any", false, false, true, 21), "html", null, true);
                        yield "\" alt=\"";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 21), "cover", [], "any", false, false, true, 21), "alt", [], "any", false, false, true, 21), "html", null, true);
                        yield "\"></a>
          ";
                    } else {
                        // line 22
                        yield "<p><a";
                        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 22), "embed_id", [], "any", false, false, true, 22)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                            yield " class=\"video-link\" data-video-title=\"";
                            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 22), "title", [], "any", false, false, true, 22), "html", null, true);
                            yield "\"";
                        }
                        yield " href=\"";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 22), "url", [], "any", false, false, true, 22), "html", null, true);
                        yield "\">";
                        yield (string) (((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 22), "title", [], "any", false, false, true, 22)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ($this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 22), "title", [], "any", false, false, true, 22), "html", null, true)) : ($this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 22), "url", [], "any", false, false, true, 22), "html", null, true)));
                        yield "</a></p>";
                    }
                    // line 23
                    yield "          ";
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 23), "title", [], "any", false, false, true, 23)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        yield "<p class=\"text-alert\">";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "video", [], "any", false, false, true, 23), "title", [], "any", false, false, true, 23), "html", null, true);
                        yield "</p>";
                    }
                    // line 24
                    yield "        </div>
      ";
                }
                // line 26
                yield "    </div>
  ";
            }
            // line 28
            yield "  ";
            if (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "disease_heading", [], "any", false, false, true, 28)) && $tmp instanceof Markup ? (string) $tmp : $tmp) || (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "overview", [], "any", false, false, true, 28)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
                // line 29
                yield "    <div class=\"row\"><div class=\"col-12\">
      ";
                // line 30
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "disease_heading", [], "any", false, false, true, 30)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    yield "<h2>";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "disease_heading", [], "any", false, false, true, 30), "html", null, true);
                    yield "</h2>";
                }
                // line 31
                yield "      ";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "overview", [], "any", false, false, true, 31), "html", null, true);
                yield "
    </div></div>
  ";
            }
            // line 34
            yield "  ";
            if ((((((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "onset", [], "any", false, false, true, 34)) && $tmp instanceof Markup ? (string) $tmp : $tmp) || (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "cause", [], "any", false, false, true, 34)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) || (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "symptoms", [], "any", false, false, true, 34)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) || (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "unmet_need", [], "any", false, false, true, 34)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) || (($tmp = ($context["show_back"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
                // line 35
                yield "    <div class=\"row mt-2 pt-1 pt-lg-0 mt-lg-5\">
      <div class=\"col-12 col-md-6";
                // line 36
                if ( !(($tmp = ($context["mobile"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    yield " pe-md-4";
                }
                yield "\">
        ";
                // line 37
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "onset", [], "any", false, false, true, 37)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    yield "<h3>";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "disease_short", [], "any", false, false, true, 37), "html", null, true);
                    yield " ";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Onset"));
                    yield "</h3>";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "onset", [], "any", false, false, true, 37), "html", null, true);
                }
                // line 38
                yield "        ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "cause", [], "any", false, false, true, 38)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    yield "<h3>";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "disease_short", [], "any", false, false, true, 38), "html", null, true);
                    yield " ";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Cause"));
                    yield "</h3>";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "cause", [], "any", false, false, true, 38), "html", null, true);
                }
                // line 39
                yield "      </div>
      <div class=\"col-12 col-md-6 mt-2 pt-1";
                // line 40
                if ((($tmp = ($context["mobile"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    yield " p-lg-0 m-lg-0";
                } else {
                    yield " pt-md-0 mt-md-0 ps-md-4";
                }
                yield "\">
        ";
                // line 41
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "symptoms", [], "any", false, false, true, 41)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    yield "<h3>";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "disease_short", [], "any", false, false, true, 41), "html", null, true);
                    yield " ";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Symptoms"));
                    yield "</h3>";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "symptoms", [], "any", false, false, true, 41), "html", null, true);
                }
                // line 42
                yield "        ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "unmet_need", [], "any", false, false, true, 42)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    yield "<h3>";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "disease_short", [], "any", false, false, true, 42), "html", null, true);
                    yield " ";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Unmet Therapeutic Need"));
                    yield "</h3>";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["program"] ?? null), "unmet_need", [], "any", false, false, true, 42), "html", null, true);
                }
                // line 43
                yield "      </div>
      ";
                // line 44
                if ((($tmp = ($context["show_back"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    yield "<div class=\"back-to w-100 ms-3 smooth-scroll\"><a class=\"btn btn-primary\" href=\"";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["back_url"] ?? null), "html", null, true);
                    yield "\">";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Back to pipeline"));
                    yield "</a></div>";
                }
                // line 45
                yield "    </div>
  ";
            }
            // line 47
            yield "  ";
            if ( !(($tmp = ($context["mobile"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "</div>";
            }
            yield from [];
        })(), false))) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/custom/dynetx/templates/paragraph--dyne-program-pipeline.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  573 => 47,  569 => 45,  561 => 44,  558 => 43,  548 => 42,  539 => 41,  531 => 40,  528 => 39,  518 => 38,  509 => 37,  503 => 36,  500 => 35,  497 => 34,  490 => 31,  484 => 30,  481 => 29,  478 => 28,  474 => 26,  470 => 24,  463 => 23,  450 => 22,  439 => 21,  432 => 19,  421 => 18,  419 => 17,  416 => 16,  414 => 15,  411 => 14,  395 => 13,  390 => 12,  387 => 11,  377 => 10,  370 => 9,  366 => 7,  355 => 6,  353 => 5,  342 => 4,  339 => 3,  334 => 2,  319 => 1,  305 => 109,  301 => 108,  298 => 107,  294 => 106,  290 => 104,  284 => 101,  279 => 100,  273 => 99,  270 => 98,  267 => 97,  259 => 95,  256 => 94,  249 => 93,  241 => 92,  237 => 90,  229 => 88,  222 => 87,  193 => 86,  187 => 85,  184 => 84,  180 => 83,  165 => 81,  162 => 80,  153 => 79,  145 => 78,  140 => 77,  126 => 76,  120 => 74,  117 => 73,  113 => 72,  107 => 69,  103 => 68,  99 => 67,  95 => 66,  90 => 64,  84 => 63,  81 => 62,  75 => 61,  69 => 57,  62 => 54,  56 => 53,  53 => 52,  51 => 51,  47 => 50,  44 => 49,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/custom/dynetx/templates/paragraph--dyne-program-pipeline.html.twig", "/var/www/html/web/themes/custom/dynetx/templates/paragraph--dyne-program-pipeline.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["macro" => 1, "if" => 51, "for" => 72, "set" => 73];
        static $filters = ["escape" => 50, "t" => 63, "replace" => 77, "length" => 85];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "macro", 1 => "if", 2 => "for", 3 => "set"],
                [0 => "escape", 1 => "t", 2 => "replace", 3 => "length"],
                [],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            } elseif ($e instanceof SecurityNotAllowedTestError && isset($tests[$e->getTestName()])) {
                $e->setTemplateLine($tests[$e->getTestName()]);
            }

            throw $e;
        }

    }
}
