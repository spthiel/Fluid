<?php
declare(strict_types=1);
namespace TYPO3Fluid\Fluid\Core\Parser;

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

use TYPO3Fluid\Fluid\Component\ComponentInterface;
use TYPO3Fluid\Fluid\Component\Error\ChildNotFoundException;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\AbstractNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;
use TYPO3Fluid\Fluid\Core\Variables\VariableProviderInterface;
use TYPO3Fluid\Fluid\View\Exception;

/**
 * Stores all information relevant for one parsing pass - that is, the root node,
 * and the current stack of open nodes (nodeStack) and a variable container used
 * for PostParseFacets.
 */
class ParsingState extends AbstractNode implements ParsedTemplateInterface
{

    /**
     * @var string
     */
    protected $identifier = '';

    /**
     * Root node reference
     *
     * @var RootNode
     */
    protected $rootNode;

    /**
     * Array of node references currently open.
     *
     * @var array
     */
    protected $nodeStack = [];

    /**
     * Variable container where ViewHelpers implementing the PostParseFacet can
     * store things in.
     *
     * @var VariableProviderInterface
     */
    protected $variableContainer;

    public function __construct()
    {
        $this->rootNode = new RootNode();
        $this->nodeStack[] = $this->rootNode;
    }

    public function getNamedChild(string $name): ComponentInterface
    {
        return $this->rootNode->getNamedChild($name);
    }

    /**
     * @var boolean
     */
    protected $compiled = false;

    /**
     * @param string $identifier
     * @return void
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Injects a variable container to be used during parsing.
     *
     * @param VariableProviderInterface $variableContainer
     * @return void
     */
    public function setVariableProvider(VariableProviderInterface $variableContainer): void
    {
        $this->variableContainer = $variableContainer;
    }

    /**
     * Set root node of this parsing state.
     *
     * @param RootNode $rootNode
     * @return void
     */
    public function setRootNode(RootNode $rootNode): void
    {
        $this->rootNode = $rootNode;
    }

    /**
     * Get root node of this parsing state.
     *
     * @return RootNode The root node
     */
    public function getRootNode(): RootNode
    {
        return $this->rootNode;
    }

    public function evaluate(RenderingContextInterface $renderingContext)
    {
        return $this->render($renderingContext);
    }

    /**
     * Render the parsed template with rendering context
     *
     * @param RenderingContextInterface $renderingContext The rendering context to use
     * @return mixed Rendered string
     */
    public function render(RenderingContextInterface $renderingContext)
    {
        return $this->getRootNode()->execute($renderingContext);
    }

    /**
     * Push a node to the node stack. The node stack holds all currently open
     * templating tags.
     *
     * @param ComponentInterface $node Node to push to node stack
     * @return void
     */
    public function pushNodeToStack(ComponentInterface $node): void
    {
        $this->nodeStack[] = $node;
    }

    /**
     * Get the top stack element, without removing it.
     *
     * @return ?NodeInterface the top stack element.
     */
    public function getNodeFromStack(): ?ComponentInterface
    {
        return end($this->nodeStack) ?: null;
    }

    /**
     * Pop the top stack element (=remove it) and return it back.
     *
     * @return ComponentInterface|null the top stack element, which was removed.
     */
    public function popNodeFromStack(): ?ComponentInterface
    {
        return array_pop($this->nodeStack) ?: null;
    }

    /**
     * Count the size of the node stack
     *
     * @return integer Number of elements on the node stack (i.e. number of currently open Fluid tags)
     */
    public function countNodeStack(): int
    {
        return count($this->nodeStack);
    }

    /**
     * Returns a variable container which will be then passed to the postParseFacet.
     *
     * @return VariableProviderInterface|null The variable container or NULL if none has been set yet
     */
    public function getVariableContainer(): VariableProviderInterface
    {
        return $this->variableContainer ?? new StandardVariableProvider();
    }

    /**
     * Returns the name of the layout that is defined within the current template via <f:layout name="..." />
     * If no layout is defined, this returns NULL
     * This requires the current rendering context in order to be able to evaluate the layout name
     *
     * @param RenderingContextInterface $renderingContext
     * @return string|null
     * @throws Exception
     */
    public function getLayoutName(RenderingContextInterface $renderingContext): ?string
    {
        try {
            $layoutName = $this->rootNode->getNamedChild('layoutName');
            return $layoutName->execute($renderingContext);
        } catch (ChildNotFoundException $exception) {
            return null;
        }
    }
}
