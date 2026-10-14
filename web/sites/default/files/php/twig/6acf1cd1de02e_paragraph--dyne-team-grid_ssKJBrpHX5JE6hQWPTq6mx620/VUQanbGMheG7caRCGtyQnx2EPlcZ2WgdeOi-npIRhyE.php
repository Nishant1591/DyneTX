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

/* themes/custom/dynetx/templates/paragraph--dyne-team-grid.html.twig */
class __TwigTemplate_6a75dcda9701d5d3537c75b2b0d4afb8 extends Template
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
        // line 3
        $context["team_id"] = ("dyne-team-" . CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "id", [], "method", false, false, true, 3));
        // line 4
        $context["heading"] = ((CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "field_heading", [], "any", false, true, true, 4), "value", [], "any", true, true, true, 4)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "field_heading", [], "any", false, false, true, 4), "value", [], "any", false, false, true, 4), "")) : (""));
        // line 5
        $context["is_board"] =  !Twig\Extension\CoreExtension::testEmpty(($context["heading"] ?? null));
        // line 6
        $context["collapse_id"] = (($context["team_id"] ?? null) . "-members");
        // line 7
        $context["source_anchor"] = (((Twig\Extension\CoreExtension::lower($this->env->getCharset(), ($context["heading"] ?? null)) == "board of directors")) ? ("bod") : ((((Twig\Extension\CoreExtension::lower($this->env->getCharset(), ($context["heading"] ?? null)) == "scientific advisory board")) ? ("scientific") : (""))));
        // line 8
        yield "<div";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", ["dyne-team"], "method", false, false, true, 8), "html", null, true);
        yield ">
  <section class=\"container-fluid section ";
        // line 9
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar((((($tmp = ($context["is_board"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("boards") : ("leadership-team")));
        yield "\">
    ";
        // line 10
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "field_anchor", [], "any", false, false, true, 10), "value", [], "any", false, false, true, 10)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<span id=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "field_anchor", [], "any", false, false, true, 10), "value", [], "any", false, false, true, 10), "html", null, true);
            yield "\" class=\"scroll-anchor\"></span>";
        }
        // line 11
        yield "    <div class=\"container\">
      ";
        // line 12
        if ((($tmp = ($context["is_board"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 13
            yield "        <div class=\"row\"><div class=\"col-12\">
          <div class=\"collapse-hdr\">
            <h2 id=\"";
            // line 15
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["team_id"] ?? null), "html", null, true);
            yield "-heading\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["heading"] ?? null), "html", null, true);
            yield "</h2>
            <button class=\"btn btn-primary btn-plus-collapse stretched-link collapsed\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#";
            // line 16
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["collapse_id"] ?? null), "html", null, true);
            yield "\"";
            if ((($tmp = ($context["source_anchor"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield " data-dyne-team-anchor=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["source_anchor"] ?? null), "html", null, true);
                yield "\"";
            }
            yield " aria-expanded=\"false\" aria-controls=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["collapse_id"] ?? null), "html", null, true);
            yield "\" aria-label=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Show or hide"));
            yield " ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["heading"] ?? null), "html", null, true);
            yield "\"><i aria-hidden=\"true\"></i></button>
          </div>
      ";
        }
        // line 19
        yield "      <div class=\"row";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar((((($tmp = ($context["is_board"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (" collapse") : ("")));
        yield "\"";
        if ((($tmp = ($context["is_board"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield " id=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["collapse_id"] ?? null), "html", null, true);
            yield "\" aria-labelledby=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["team_id"] ?? null), "html", null, true);
            yield "-heading\"";
        }
        yield ">
        ";
        // line 20
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["dyne_items"] ?? null));
        $context['loop'] = [
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        ];
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["m"]) {
            // line 21
            yield "          ";
            $context["modal_id"] = ((($context["team_id"] ?? null) . "-") . CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, true, 21));
            // line 22
            yield "          <div class=\"col-12 col-sm-6 col-md-4 card\">
            ";
            // line 23
            if (( !(($tmp = ($context["is_board"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["m"], "photo", [], "any", false, false, true, 23)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
                yield "<img class=\"card-img-top\" src=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["m"], "photo", [], "any", false, false, true, 23), "html", null, true);
                yield "\" alt=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["m"], "name", [], "any", false, false, true, 23), "html", null, true);
                yield "\" />";
            }
            // line 24
            yield "            <div class=\"card-body\">
              ";
            // line 25
            if ( !(($tmp = ($context["is_board"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 26
                yield "                <button type=\"button\" class=\"btn btn-primary btn-plus stretched-link\" data-bs-toggle=\"modal\" data-bs-target=\"#modalTeam\" data-dyne-team-bio=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["modal_id"] ?? null), "html", null, true);
                yield "\" aria-label=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Read biography of"));
                yield " ";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["m"], "name", [], "any", false, false, true, 26), "html", null, true);
                yield "\"><i aria-hidden=\"true\"></i></button>
              ";
            }
            // line 28
            yield "              <h4 class=\"card-title\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["m"], "name", [], "any", false, false, true, 28), "html", null, true);
            yield "</h4>
              <p class=\"card-text\">";
            // line 29
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["m"], "role", [], "any", false, false, true, 29), "html", null, true);
            yield "</p>
            </div>
            ";
            // line 31
            if ((($tmp = ($context["is_board"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 32
                yield "              <button type=\"button\" class=\"btn btn-primary btn-plus stretched-link\" data-bs-toggle=\"modal\" data-bs-target=\"#modalTeam\" data-dyne-team-bio=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["modal_id"] ?? null), "html", null, true);
                yield "\" aria-label=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Read biography of"));
                yield " ";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["m"], "name", [], "any", false, false, true, 32), "html", null, true);
                yield "\"><i aria-hidden=\"true\"></i></button>
            ";
            }
            // line 34
            yield "          </div>
        ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['m'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent);
        $context += $_parent;
        // line 36
        yield "      </div>
      ";
        // line 37
        if ((($tmp = ($context["is_board"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "</div></div>";
        }
        // line 38
        yield "    </div>
  </section>
  ";
        // line 42
        yield "  ";
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["dyne_items"] ?? null));
        $context['loop'] = [
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        ];
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["m"]) {
            // line 43
            yield "    ";
            $context["bio_id"] = ((($context["team_id"] ?? null) . "-") . CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, true, 43));
            // line 44
            yield "    <template id=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["bio_id"] ?? null), "html", null, true);
            yield "\" data-dyne-team-noimage=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(((((($tmp = ($context["is_board"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) ||  !(($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["m"], "photo", [], "any", false, false, true, 44)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) ? ("true") : ("false")));
            yield "\" data-dyne-team-count=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "length", [], "any", false, false, true, 44), "html", null, true);
            yield "\" data-dyne-team-prev=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["team_id"] ?? null), "html", null, true);
            yield "-";
            yield (string) (((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "first", [], "any", false, false, true, 44)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ($this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "length", [], "any", false, false, true, 44), "html", null, true)) : ($this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, (CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, true, 44) - 1), "html", null, true)));
            yield "\" data-dyne-team-next=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["team_id"] ?? null), "html", null, true);
            yield "-";
            yield (string) (((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "last", [], "any", false, false, true, 44)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (1) : ($this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, (CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, true, 44) + 1), "html", null, true)));
            yield "\">
      ";
            // line 45
            if (( !(($tmp = ($context["is_board"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["m"], "photo", [], "any", false, false, true, 45)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
                // line 46
                yield "        <div class=\"col-12 col-sm-4 card image_card\">
          <img class=\"card-img-top\" src=\"";
                // line 47
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["m"], "photo", [], "any", false, false, true, 47), "html", null, true);
                yield "\" alt=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["m"], "name", [], "any", false, false, true, 47), "html", null, true);
                yield "\" />
          <div class=\"card-body\">
            <h4 class=\"card-title d-none d-sm-block\" aria-hidden=\"true\">";
                // line 49
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["m"], "name", [], "any", false, false, true, 49), "html", null, true);
                yield "</h4>
            <p class=\"card-text d-none d-sm-block\" aria-hidden=\"true\">";
                // line 50
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["m"], "role", [], "any", false, false, true, 50), "html", null, true);
                yield "</p>
          </div>
        </div>
      ";
            }
            // line 54
            yield "      <div class=\"col-12 col-sm-8 description\">
        <h4 data-dyne-team-title>";
            // line 55
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["m"], "name", [], "any", false, false, true, 55), "html", null, true);
            yield "</h4>
        <h5>";
            // line 56
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["m"], "role", [], "any", false, false, true, 56), "html", null, true);
            yield "</h5>
        <div class=\"card-description\">";
            // line 57
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(CoreExtension::getAttribute($this->env, $this->source, $context["m"], "bio", [], "any", false, false, true, 57));
            yield "</div>
      </div>
    </template>
  ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['m'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent);
        $context += $_parent;
        // line 61
        yield "</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["paragraph", "attributes", "dyne_items", "loop"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/custom/dynetx/templates/paragraph--dyne-team-grid.html.twig";
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
        return array (  301 => 61,  282 => 57,  278 => 56,  274 => 55,  271 => 54,  264 => 50,  260 => 49,  253 => 47,  250 => 46,  248 => 45,  231 => 44,  228 => 43,  210 => 42,  206 => 38,  202 => 37,  199 => 36,  183 => 34,  173 => 32,  171 => 31,  166 => 29,  161 => 28,  151 => 26,  149 => 25,  146 => 24,  138 => 23,  135 => 22,  132 => 21,  115 => 20,  102 => 19,  84 => 16,  78 => 15,  74 => 13,  72 => 12,  69 => 11,  63 => 10,  59 => 9,  54 => 8,  52 => 7,  50 => 6,  48 => 5,  46 => 4,  44 => 3,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/custom/dynetx/templates/paragraph--dyne-team-grid.html.twig", "/var/www/html/web/themes/custom/dynetx/templates/paragraph--dyne-team-grid.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 3, "if" => 10, "for" => 20];
        static $filters = ["default" => 4, "lower" => 7, "escape" => 8, "t" => 16, "raw" => 57];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "set", 1 => "if", 2 => "for"],
                [0 => "default", 1 => "lower", 2 => "escape", 3 => "t", 4 => "raw"],
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
