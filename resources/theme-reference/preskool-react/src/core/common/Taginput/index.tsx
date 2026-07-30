import React, { useState, useRef, useEffect } from 'react';
import { Input, type InputRef, Tag, theme } from 'antd';

interface TagInputProps {
  initialTags?: string[];
  onTagsChange?: (tags: string[]) => void;
}

const TagInput: React.FC<TagInputProps> = ({ initialTags = [], onTagsChange }) => {
  const {  } = theme.useToken();
  const [tags, setTags] = useState<string[]>(initialTags);
  const [inputValue, setInputValue] = useState('');
  const [inputVisible, ] = useState(false);
  const inputRef = useRef<InputRef | null>(null);

  useEffect(() => {
    if (inputVisible && inputRef.current) {
      inputRef.current.focus();
    }
  }, [inputVisible]);

  const handleClose = (removedTag: string) => {
    const newTags = tags.filter((tag) => tag !== removedTag);
    setTags(newTags);
    onTagsChange?.(newTags); // Notify parent component about the change
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setInputValue(e.target.value);
  };

  const handleInputConfirm = () => {
    if (inputValue && tags.indexOf(inputValue) === -1) {
      const newTags = [...tags, inputValue];
      setTags(newTags);
      onTagsChange?.(newTags); // Notify parent component about the change
    }
    setInputValue('');
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter' || e.key === ',') {
      e.preventDefault();
      handleInputConfirm();
    }
  };

  const renderTags = () =>
    tags.map((tag) => (
      <Tag key={tag} closable onClose={() => handleClose(tag)}>
        {tag}
      </Tag>
    ));

  return (
    <div className='custome-tag-input'>
      {renderTags()}
      <Input
        ref={inputRef}
        type="text"
        size="small"
        style={{ flex: 1, minWidth: 100, maxWidth: '300px' }}
        value={inputValue}
        onChange={handleInputChange}
        onKeyDown={handleKeyDown}
        onBlur={handleInputConfirm}
        placeholder="Add a tag"
        autoFocus={inputVisible}
      />
    </div>
  );
};

export default TagInput;
