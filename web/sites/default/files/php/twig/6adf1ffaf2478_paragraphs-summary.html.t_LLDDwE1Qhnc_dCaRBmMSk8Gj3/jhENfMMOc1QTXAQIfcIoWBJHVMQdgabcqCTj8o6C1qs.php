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

/* modules/contrib/paragraphs/templates/paragraphs-summary.html.twig */
class __TwigTemplate_61ffe64baef024e39577c46dd32efe7c extends Template
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
        // line 16
        $context["classes"] = ["paragraphs-description", (((($tmp =         // line 18
($context["expanded"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("paragraphs-expanded-description") : ("paragraphs-collapsed-description"))];
        // line 20
        $_v0 = ('' === $tmp = implode('', iterator_to_array((function () use (&$context, $macros, $blocks) {
            // line 21
            yield "  ";
            if (( !Twig\Extension\CoreExtension::testEmpty(($context["content"] ?? null)) ||  !Twig\Extension\CoreExtension::testEmpty(($context["behaviors"] ?? null)))) {
                // line 22
                yield "    <div";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 22), "html", null, true);
                yield ">
      ";
                // line 23
                if ( !Twig\Extension\CoreExtension::testEmpty(($context["content"] ?? null))) {
                    // line 24
                    yield "        <div class=\"paragraphs-content-wrapper\">";
                    // line 25
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(($context["content"] ?? null));
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
                    foreach ($context['_seq'] as $context["_key"] => $context["content_item"]) {
                        // line 26
                        yield "<span class=\"summary-content\">";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $context["content_item"], "html", null, true);
                        yield "</span>";
                        // line 27
                        if ( !(($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "last", [], "any", false, false, true, 27)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                            yield ", ";
                        }
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
                    unset($context['_seq'], $context['_key'], $context['content_item'], $context['_parent'], $context['loop']);
                    $context = array_intersect_key($context, $_parent);
                    $context += $_parent;
                    // line 29
                    yield "</div>
      ";
                }
                // line 31
                yield "      ";
                if ( !Twig\Extension\CoreExtension::testEmpty(($context["behaviors"] ?? null))) {
                    // line 32
                    yield "        <div class=\"paragraphs-plugin-wrapper\">";
                    // line 33
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(($context["behaviors"] ?? null));
                    foreach ($context['_seq'] as $context["_key"] => $context["behavior_item"]) {
                        // line 34
                        yield "<span class=\"summary-plugin\">";
                        // line 35
                        if ( !(null === CoreExtension::getAttribute($this->env, $this->source, $context["behavior_item"], "label", [], "any", false, false, true, 35))) {
                            // line 36
                            yield "<span class=\"summary-plugin-label\">";
                            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["behavior_item"], "label", [], "any", false, false, true, 36), "html", null, true);
                            yield "</span>";
                        }
                        // line 38
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["behavior_item"], "value", [], "any", false, false, true, 38), "html", null, true);
                        // line 39
                        yield "</span>";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['behavior_item'], $context['_parent']);
                    $context = array_intersect_key($context, $_parent);
                    $context += $_parent;
                    // line 41
                    yield "</div>
      ";
                }
                // line 43
                yield "    </div>
  ";
            }
            yield from [];
        })(), false))) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 20
        yield (string) Twig\Extension\CoreExtension::spaceless($this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $_v0, "html", null, true));
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["expanded", "content", "behaviors", "attributes", "loop"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "modules/contrib/paragraphs/templates/paragraphs-summary.html.twig";
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
        return array (  140 => 20,  134 => 43,  130 => 41,  123 => 39,  121 => 38,  116 => 36,  114 => 35,  112 => 34,  108 => 33,  106 => 32,  103 => 31,  99 => 29,  82 => 27,  78 => 26,  61 => 25,  59 => 24,  57 => 23,  52 => 22,  49 => 21,  47 => 20,  45 => 18,  44 => 16,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "modules/contrib/paragraphs/templates/paragraphs-summary.html.twig", "/var/www/html/web/modules/contrib/paragraphs/templates/paragraphs-summary.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 16, "apply" => 20, "if" => 21, "for" => 25];
        static $filters = ["escape" => 22, "spaceless" => 20];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "set", 1 => "apply", 2 => "if", 3 => "for"],
                [0 => "escape", 1 => "spaceless"],
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
